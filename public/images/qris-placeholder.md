# QRIS Image Placeholder

## Required File
Place your QRIS image at: `public/images/qris-andiklaundry.png`

## Requirements
- Format: PNG
- Recommended size: 200x200 to 300x300 pixels
- Transparent background preferred

## How to Get QRIS
1. Contact your payment gateway provider (Midtrans, Xendit, etc.)
2. Download your store's QRIS image
3. Rename it to `qris-andiklaundry.png`
4. Place it in `public/images/`

## Reference
The image is loaded in `resources/js/Pages/Receipt/Show.vue`:
```html
<img src="/images/qris-andiklaundry.png" alt="QRIS Payment" />
```

## Notes
- If the image is not found, it will silently fail to display
- Make sure the file exists before expecting it to show in the receipt page
