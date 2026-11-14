package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"

	"github.com/mdp/qrterminal/v3"
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
	qrLock    sync.RWMutex
	currentQR string
}

type SendTextRequest struct {
	Phone string `json:"Phone"`
	Body  string `json:"Body"`
	ID    string `json:"Id,omitempty"`
}

type ApiResponse struct {
	Code    int         `json:"code"`
	Success bool        `json:"success"`
	Message string      `json:"message,omitempty"`
	Data    interface{} `json:"data,omitempty"`
}

func main() {
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
		client: client,
	}

	client.AddEventHandler(func(evt interface{}) {
		switch v := evt.(type) {
		case *events.Connected:
			appLogger.Infof("WhatsApp WebSocket berhasil terhubung!")
		case *events.LoggedOut:
			appLogger.Warnf("WhatsApp Device logout: %v", v.Reason)
		}
	})

	if client.Store.ID == nil {
		qrChan, _ := client.GetQRChannel(context.Background())
		err = client.Connect()
		if err != nil {
			log.Fatalf("Gagal menghubungkan client WhatsApp: %v", err)
		}
		go func() {
			for evt := range qrChan {
				if evt.Event == "code" {
					srv.qrLock.Lock()
					srv.currentQR = evt.Code
					srv.qrLock.Unlock()

					fmt.Println("\n========================================================")
					fmt.Println("  SCAN QR CODE INI DENGAN WHATSAPP DI PONSEL ANDA       ")
					fmt.Println("========================================================")
					qrterminal.GenerateHalfBlock(evt.Code, qrterminal.L, os.Stdout)
					fmt.Println("========================================================")
				} else {
					appLogger.Infof("QR Channel event: %s", evt.Event)
				}
			}
		}()
	} else {
		err = client.Connect()
		if err != nil {
			log.Fatalf("Gagal menghubungkan kembali client WhatsApp: %v", err)
		}
		appLogger.Infof("Sesi WhatsApp tersimpan ditemukan untuk JID: %s", client.Store.ID.String())
	}

	mux := http.NewServeMux()

	// Health check
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

		if loggedIn {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "Koneksi ke gateway WhatsApp berhasil terhubung.",
				Data: map[string]interface{}{
					"connected": connected,
					"loggedIn":  loggedIn,
					"jid":       jid,
				},
			})
		} else {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "Gateway aktif, silakan scan QR code untuk pairing WhatsApp.",
				Data: map[string]interface{}{
					"connected": connected,
					"loggedIn":  false,
					"state":     "scan_qr_code",
					"qr":        srv.getQR(),
				},
			})
		}
	})

	// GET /session/qr
	mux.HandleFunc("/session/qr", func(w http.ResponseWriter, r *http.Request) {
		qr := srv.getQR()
		if qr == "" && srv.client.IsLoggedIn() {
			jsonResponse(w, http.StatusOK, ApiResponse{
				Code:    200,
				Success: true,
				Message: "Device sudah terhubung dan login.",
				Data: map[string]interface{}{
					"loggedIn": true,
				},
			})
			return
		}

		jsonResponse(w, http.StatusOK, ApiResponse{
			Code:    200,
			Success: true,
			Data: map[string]interface{}{
				"qr":       qr,
				"loggedIn": srv.client.IsLoggedIn(),
			},
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
				Message: "WhatsApp gateway belum login/terhubung. Silakan scan QR code terlebih dahulu.",
			})
			return
		}

		// Normalize phone
		cleanPhone := strings.TrimPrefix(strings.TrimSpace(req.Phone), "+")
		cleanPhone = strings.ReplaceAll(cleanPhone, "-", "")
		cleanPhone = strings.ReplaceAll(cleanPhone, " ", "")
		if strings.HasPrefix(cleanPhone, "0") {
			cleanPhone = "62" + cleanPhone[1:]
		} else if strings.HasPrefix(cleanPhone, "8") {
			cleanPhone = "62" + cleanPhone
		}

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
		Addr:    ":" + port,
		Handler: mux,
	}

	appLogger.Infof("LMS WhatsApp Gateway berjalan di http://localhost:%s", port)

	stopChan := make(chan os.Signal, 1)
	signal.Notify(stopChan, os.Interrupt, syscall.SIGTERM)

	go func() {
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("HTTP server error: %v", err)
		}
	}()

	<-stopChan
	appLogger.Infof("Menghentikan WhatsApp Gateway...")
	client.Disconnect()
	server.Shutdown(context.Background())
}

func (s *GatewayServer) getQR() string {
	s.qrLock.RLock()
	defer s.qrLock.RUnlock()
	return s.currentQR
}

func jsonResponse(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}
