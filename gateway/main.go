package main

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/mdp/qrterminal/v3"
	"github.com/skip2/go-qrcode"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/store/sqlstore"
	"go.mau.fi/whatsmeow/types"
	"go.mau.fi/whatsmeow/types/events"
	waLog "go.mau.fi/whatsmeow/util/log"
	"google.golang.org/protobuf/proto"
	_ "modernc.org/sqlite"
)

type GatewayServer struct {
	client    *whatsmeow.Client
	container *sqlstore.Container
	appLogger waLog.Logger

	qrLock    sync.RWMutex
	currentQR string
	qrImage   string // data:image/png;base64,...

	connLock sync.Mutex
}

type SendTextRequest struct {
	Phone string `json:"Phone"`
	Body  string `json:"Body"`
	ID    string `json:"Id,omitempty"`
}

type PairCodeRequest struct {
	Phone string `json:"phone"`
}

type ApiResponse struct {
	Code    int         `json:"code"`
	Success bool        `json:"success"`
	Message string      `json:"message,omitempty"`
	Data    interface{} `json:"data,omitempty"`
}

func main() {
	host := os.Getenv("HOST")
	if host == "" {
		host = "127.0.0.1"
	}
	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	appLogger := waLog.Stdout("Main", "INFO", true)
	dbLog := waLog.Stdout("Database", "WARN", true)

	ctx := context.Background()

	container, err := sqlstore.New(ctx, "sqlite", "file:wuzapi.db?_pragma=foreign_keys(1)", dbLog)
	if err != nil {
		log.Fatalf("Gagal inisialisasi SQLite database: %v", err)
	}

	deviceStore, err := container.GetFirstDevice(ctx)
	if err != nil {
		log.Fatalf("Gagal memuat device store: %v", err)
	}

	clientLog := waLog.Stdout("Client", "INFO", true)
	client := whatsmeow.NewClient(deviceStore, clientLog)

	srv := &GatewayServer{
		client:    client,
		container: container,
		appLogger: appLogger,
	}

	client.AddEventHandler(func(evt interface{}) {
		switch v := evt.(type) {
		case *events.Connected:
			appLogger.Infof("WhatsApp WebSocket berhasil terhubung!")
		case *events.LoggedOut:
			appLogger.Warnf("WhatsApp Device logout: %v", v.Reason)
			srv.qrLock.Lock()
			srv.currentQR = ""
			srv.qrImage = ""
			srv.qrLock.Unlock()
		}
	})

	srv.startConnection(false)

	mux := http.NewServeMux()

	// Global CORS middleware
	corsHandler := func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			w.Header().Set("Access-Control-Allow-Origin", "*")
			w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS, DELETE")
			w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, Token, X-Requested-With")
			if r.Method == http.MethodOptions {
				w.WriteHeader(http.StatusOK)
				return
			}
			next.ServeHTTP(w, r)
		})
	}

	// GET / - Health check
	mux.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/" {
			http.NotFound(w, r)
			return
		}
		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Message: "LMS Andik Laundry WhatsApp Gateway (whatsmeow) is running.",
			Data: map[string]interface{}{
				"status":    "healthy",
				"connected": srv.client.IsConnected(),
				"loggedIn":  srv.client.IsLoggedIn(),
			},
		})
	})

	// GET /session/status
	mux.HandleFunc("/session/status", func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodGet {
			jsonResponse(w, http.StatusMethodNotAllowed, ApiResponse{Code: 405, Success: false, Message: "Method not allowed"})
			return
		}

		connected := srv.client.IsConnected()
		loggedIn := srv.client.IsLoggedIn()
		jid := ""
		if srv.client.Store.ID != nil {
			jid = srv.client.Store.ID.String()
		}

		qr, qrImg := srv.getQRData()

		if loggedIn {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "Koneksi ke gateway WhatsApp berhasil terhubung.",
				Data: map[string]interface{}{
					"connected": connected,
					"loggedIn":  true,
					"jid":       jid,
					"state":     "authenticated",
				},
			})
		} else {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "Gateway aktif, silakan scan QR code atau gunakan kode OTP pairing untuk menghubungkan WhatsApp.",
				Data: map[string]interface{}{
					"connected": connected,
					"loggedIn":  false,
					"state":     "scan_qr_code",
					"qr":        qr,
					"qr_image":  qrImg,
				},
			})
		}
	})

	// GET /session/qr
	mux.HandleFunc("/session/qr", func(w http.ResponseWriter, r *http.Request) {
		if srv.client.IsLoggedIn() {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "Device sudah terhubung dan login.",
				Data: map[string]interface{}{
					"loggedIn":  true,
					"connected": srv.client.IsConnected(),
				},
			})
			return
		}

		// Ensure connection if timed out
		if !srv.client.IsConnected() {
			srv.startConnection(true)
			time.Sleep(800 * time.Millisecond)
		}

		qr, qrImg := srv.getQRData()
		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Data: map[string]interface{}{
				"qr":        qr,
				"qr_image":  qrImg,
				"loggedIn":  srv.client.IsLoggedIn(),
				"connected": srv.client.IsConnected(),
			},
		})
	})

	// POST /session/connect - Start / Refresh connection & QR
	mux.HandleFunc("/session/connect", func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			jsonResponse(w, http.StatusMethodNotAllowed, ApiResponse{Code: 405, Success: false, Message: "Method not allowed"})
			return
		}

		if srv.client.IsLoggedIn() {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "WhatsApp sudah terhubung dan siap digunakan.",
				Data: map[string]interface{}{
					"loggedIn":  true,
					"connected": srv.client.IsConnected(),
					"jid":       srv.client.Store.ID.String(),
				},
			})
			return
		}

		srv.startConnection(true)
		time.Sleep(1 * time.Second)

		qr, qrImg := srv.getQRData()
		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Message: "Sesi koneksi diperbarui. Silakan scan QR code atau minta kode OTP pairing.",
			Data: map[string]interface{}{
				"connected": srv.client.IsConnected(),
				"loggedIn":  srv.client.IsLoggedIn(),
				"qr":        qr,
				"qr_image":  qrImg,
			},
		})
	})

	// POST /session/pair-code - Get WhatsApp phone pairing code (OTP)
	mux.HandleFunc("/session/pair-code", func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			jsonResponse(w, http.StatusMethodNotAllowed, ApiResponse{Code: 405, Success: false, Message: "Method not allowed"})
			return
		}

		var req PairCodeRequest
		err := json.NewDecoder(r.Body).Decode(&req)
		if err != nil {
			jsonResponse(w, http.StatusBadRequest, ApiResponse{Code: 400, Success: false, Message: "Format JSON tidak valid"})
			return
		}

		cleanPhone := normalizePhoneNumber(req.Phone)
		if len(cleanPhone) < 8 {
			jsonResponse(w, http.StatusUnprocessableEntity, ApiResponse{
				Code:    422,
				Success: false,
				Message: "Nomor WhatsApp tidak valid. Masukkan nomor HP aktif (contoh: 081234567890 / 6281234567890).",
			})
			return
		}

		if srv.client.IsLoggedIn() {
			jsonResponse(w, http.StatusBadRequest, ApiResponse{
				Code:    400,
				Success: false,
				Message: fmt.Sprintf("WhatsApp sudah terhubung pada nomor %s. Logout terlebih dahulu jika ingin menautkan nomor lain.", srv.client.Store.ID.String()),
			})
			return
		}

		// Ensure connected to WhatsApp socket before requesting pair code
		if !srv.client.IsConnected() {
			srv.startConnection(true)
			// Wait for connection and initial QR event
			time.Sleep(1500 * time.Millisecond)
		}

		appLogger.Infof("Meminta pairing code untuk nomor: %s", cleanPhone)
		code, err := srv.client.PairPhone(ctx, cleanPhone, true, whatsmeow.PairClientChrome, "Chrome (Windows)")
		if err != nil {
			appLogger.Errorf("Gagal membuat pairing code untuk %s: %v", cleanPhone, err)
			jsonResponse(w, http.StatusUnprocessableEntity, ApiResponse{
				Code:    422,
				Success: false,
				Message: fmt.Sprintf("Gagal meminta kode pairing dari WhatsApp: %v. Pastikan nomor HP benar dan gateway terhubung ke internet.", err),
			})
			return
		}

		formattedCode := code
		if len(code) == 8 {
			formattedCode = fmt.Sprintf("%s-%s", code[:4], code[4:])
		}

		appLogger.Infof("Pairing code berhasil dibuat untuk %s: %s", cleanPhone, formattedCode)

		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Message: "Kode pairing WhatsApp berhasil dibuat. Masukkan kode ini pada WhatsApp di HP Anda.",
			Data: map[string]interface{}{
				"pairing_code":   code,
				"formatted_code": formattedCode,
				"phone":          cleanPhone,
				"instructions": []string{
					"Buka aplikasi WhatsApp di HP Anda",
					"Buka menu Titik Tiga (Android) atau Pengaturan (iOS) -> Perangkat Tertaut (Linked Devices)",
					"Ketuk 'Tautkan Perangkat' (Link a Device)",
					"Pilih 'Tautkan dengan nomor telepon saja' (Link with phone number instead)",
					"Masukkan 8 digit kode yang tertera di layar",
				},
			},
		})
	})

	// POST /session/logout - Disconnect & logout device
	mux.HandleFunc("/session/logout", func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			jsonResponse(w, http.StatusMethodNotAllowed, ApiResponse{Code: 405, Success: false, Message: "Method not allowed"})
			return
		}

		if srv.client.IsLoggedIn() {
			err := srv.client.Logout(context.Background())
			if err != nil {
				appLogger.Warnf("Gagal logout: %v", err)
			}
		}

		srv.client.Disconnect()
		srv.qrLock.Lock()
		srv.currentQR = ""
		srv.qrImage = ""
		srv.qrLock.Unlock()

		// Reset device store
		_ = srv.container.DeleteDevice(ctx, srv.client.Store)
		deviceStore, err := srv.container.GetFirstDevice(ctx)
		if err == nil {
			srv.client = whatsmeow.NewClient(deviceStore, clientLog)
		}

		srv.startConnection(true)

		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Message: "Sesi WhatsApp berhasil diputuskan (logout).",
		})
	})

	// POST /chat/send/text
	mux.HandleFunc("/chat/send/text", func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			jsonResponse(w, http.StatusMethodNotAllowed, ApiResponse{Code: 405, Success: false, Message: "Method not allowed"})
			return
		}

		var req SendTextRequest
		err := json.NewDecoder(r.Body).Decode(&req)
		if err != nil {
			jsonResponse(w, http.StatusBadRequest, ApiResponse{Code: 400, Success: false, Message: "Format JSON tidak valid"})
			return
		}

		if strings.TrimSpace(req.Phone) == "" || strings.TrimSpace(req.Body) == "" {
			jsonResponse(w, http.StatusUnprocessableEntity, ApiResponse{
				Code:    422,
				Success: false,
				Message: "Phone dan Body wajib diisi.",
			})
			return
		}

		if !srv.client.IsConnected() || !srv.client.IsLoggedIn() {
			jsonResponse(w, http.StatusUnprocessableEntity, ApiResponse{
				Code:    422,
				Success: false,
				Message: "WhatsApp gateway belum login/terhubung. Silakan scan QR code atau gunakan kode OTP pairing terlebih dahulu.",
			})
			return
		}

		cleanPhone := normalizePhoneNumber(req.Phone)
		recipientJID := types.NewJID(cleanPhone, types.DefaultUserServer)

		msg := &waE2E.Message{
			Conversation: proto.String(req.Body),
		}

		resp, err := srv.client.SendMessage(context.Background(), recipientJID, msg)
		if err != nil {
			appLogger.Errorf("Gagal mengirim pesan ke %s: %v", cleanPhone, err)
			jsonResponse(w, http.StatusUnprocessableEntity, ApiResponse{
				Code:    422,
				Success: false,
				Message: fmt.Sprintf("Gagal mengirim pesan WhatsApp: %v", err),
			})
			return
		}

		appLogger.Infof("Pesan berhasil terkirim ke %s (MsgID: %s)", cleanPhone, resp.ID)
		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Message: "Pesan WhatsApp berhasil dikirim.",
			Data: map[string]interface{}{
				"id":      resp.ID,
				"details": "Message sent",
			},
		})
	})

	server := &http.Server{
		Addr:    fmt.Sprintf("%s:%s", host, port),
		Handler: corsHandler(mux),
	}

	appLogger.Infof("LMS WhatsApp Gateway berjalan di http://%s:%s", host, port)

	stopChan := make(chan os.Signal, 1)
	signal.Notify(stopChan, os.Interrupt, syscall.SIGTERM)

	go func() {
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("HTTP server error: %v", err)
		}
	}()

	<-stopChan
	appLogger.Infof("Menghentikan WhatsApp Gateway...")
	srv.client.Disconnect()
	server.Shutdown(context.Background())
}

func (s *GatewayServer) startConnection(forceReconnect bool) {
	s.connLock.Lock()
	defer s.connLock.Unlock()

	if s.client.IsConnected() && !forceReconnect {
		return
	}

	if forceReconnect && s.client.IsConnected() {
		s.client.Disconnect()
	}

	if s.client.Store.ID == nil {
		qrChan, _ := s.client.GetQRChannel(context.Background())
		err := s.client.Connect()
		if err != nil {
			s.appLogger.Errorf("Gagal menghubungkan client WhatsApp: %v", err)
			return
		}

		go func() {
			for evt := range qrChan {
				if evt.Event == "code" {
					pngBytes, err := qrcode.Encode(evt.Code, qrcode.Medium, 280)
					imgData := ""
					if err == nil {
						imgData = "data:image/png;base64," + base64.StdEncoding.EncodeToString(pngBytes)
					}

					s.qrLock.Lock()
					s.currentQR = evt.Code
					s.qrImage = imgData
					s.qrLock.Unlock()

					fmt.Println("\n========================================================")
					fmt.Println("  SCAN QR CODE INI DENGAN WHATSAPP DI PONSEL ANDA       ")
					fmt.Println("========================================================")
					qrterminal.GenerateHalfBlock(evt.Code, qrterminal.L, os.Stdout)
					fmt.Println("========================================================")
				} else {
					s.appLogger.Infof("QR Channel event: %s", evt.Event)
				}
			}
		}()
	} else {
		err := s.client.Connect()
		if err != nil {
			s.appLogger.Errorf("Gagal menghubungkan kembali client WhatsApp: %v", err)
			return
		}
		s.appLogger.Infof("Sesi WhatsApp tersimpan ditemukan untuk JID: %s", s.client.Store.ID.String())
	}
}

func (s *GatewayServer) getQRData() (string, string) {
	s.qrLock.RLock()
	defer s.qrLock.RUnlock()
	return s.currentQR, s.qrImage
}

func normalizePhoneNumber(phone string) string {
	clean := strings.TrimPrefix(strings.TrimSpace(phone), "+")
	clean = strings.ReplaceAll(clean, "-", "")
	clean = strings.ReplaceAll(clean, " ", "")
	if strings.HasPrefix(clean, "0") {
		clean = "62" + clean[1:]
	} else if strings.HasPrefix(clean, "8") {
		clean = "62" + clean
	}
	return clean
}

func jsonResponse(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}
