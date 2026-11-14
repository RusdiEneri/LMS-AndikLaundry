/**
 * Format numeric value as Indonesian Rupiah currency string.
 */
export function formatRupiah(amount: number | string | null | undefined): string {
    const num = Number(amount ?? 0);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(isNaN(num) ? 0 : num);
}

/**
 * Normalize phone numbers to Indonesian international format without plus (e.g. 6281234567890).
 */
export function normalizePhoneForWhatsApp(phone: string | null | undefined): string {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    }
    return cleaned;
}

export interface WhatsAppReceiptOptions {
    phone?: string;
    customerName: string;
    invoiceNumber: string;
    storeName?: string;
    items: Array<{
        name: string;
        qty: number | string;
        unit: string;
        subtotal?: number;
    }>;
    tierLabel?: string;
    grandTotal: number;
    paidAmount?: number;
    outstandingBalance?: number;
    receiptUrl?: string;
    storeFooter?: string;
}

/**
 * Generate manual WhatsApp share link with pre-populated intake confirmation template.
 */
export function generateWhatsAppReceiptLink(options: WhatsAppReceiptOptions): string {
    const phone = normalizePhoneForWhatsApp(options.phone);
    const store = options.storeName || 'Andik Laundry';
    const grandTotalFormatted = formatRupiah(options.grandTotal);
    const paid = Number(options.paidAmount ?? 0);
    const debt = Number(options.outstandingBalance ?? 0);

    const paymentStatusText = debt <= 0
        ? '✅ Lunas'
        : `⚠️ Sisa Tagihan: ${formatRupiah(debt)}`;

    const itemsSummary = options.items.length > 0
        ? options.items
            .map(item => `• ${item.name} (${item.qty} ${item.unit})` + (item.subtotal ? `: ${formatRupiah(item.subtotal)}` : ''))
            .join('\n')
        : '• Layanan Laundry';

    let text = `*${store}*\n`;
    text += `Halo Kak *${options.customerName}*, terima kasih telah mempercayakan pakaian Anda di ${store}!\n\n`;
    text += `📄 *No. Nota:* ${options.invoiceNumber}\n`;
    text += `🧺 *Rincian Layanan:*\n${itemsSummary}\n`;
    if (options.tierLabel) {
        text += `⏱️ *Kecepatan:* ${options.tierLabel}\n`;
    }
    text += `💰 *Total Tagihan:* ${grandTotalFormatted}\n`;
    if (paid > 0) {
        text += `💵 *Sudah Dibayar:* ${formatRupiah(paid)}\n`;
    }
    text += `📌 *Status Bayar:* ${paymentStatusText}\n\n`;

    if (options.receiptUrl) {
        text += `🔗 *Pantau Status Cucian & Nota Digital:*\n${options.receiptUrl}\n\n`;
    }

    text += `${options.storeFooter || 'Pakaian bersih, wangi, rapi bersama Andik Laundry!'}`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
}

/**
 * Format ISO datetime string to Indonesian readable date and time.
 */
export function formatDateTimeIndo(isoString?: string | null): string {
    if (!isoString) return '-';
    try {
        const date = new Date(isoString);
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    } catch {
        return isoString;
    }
}
