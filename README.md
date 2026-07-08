# ZiniPay Payment Gateway for Website Billing

A clean and lightweight ZiniPay integration for phpnuxbill website/customer portal payments.

## Features
- Automated payment creation using ZiniPay Hosted Invoice.
- Real-time status verification and plan auto-activation.
- Dedicated admin debug logging console with levels and filters.

## Installation

1. Copy the PHP logic file:
   - Copy `zinipay.php` $\rightarrow$ `system/paymentgateway/`
2. Copy the UI templates:
   - Copy `ui/zinipay.tpl` $\rightarrow$ `system/paymentgateway/ui/`
   - Copy `ui/zinipay_logs.tpl` $\rightarrow$ `system/paymentgateway/ui/`

## Configuration

1. In your Admin Panel, navigate to **Settings** $\rightarrow$ **Payment Gateway** and check **ZiniPay**.
2. Click **Save** to activate the gateway.
3. Navigate to **Settings** $\rightarrow$ **ZiniPay Settings** to enter credentials.
4. Configure the MikroTik Walled Garden rules:
   ```routeros
   /ip hotspot walled-garden
   add dst-host=zinipay.com
   add dst-host=*.zinipay.com
   add dst-host=api.zinipay.com
   ```

## Useful Links
- **Official Website**: [ZiniPay](https://zinipay.com)
- **Developer Documentation**: [ZiniPay Docs](https://zinipay.com/docs)
- **Merchant Panel**: [ZiniPay Dashboard](https://dash.zinipay.com)

## Support the Developer

Developed by **mahmud**.

If you found this project helpful for your business or personal use, consider supporting the development. Your support helps maintain the project and fuels new features!

[![Support](https://img.shields.io/badge/SUPPORT-BUY%20ME%20A%20COFFEE-ff5f9e?style=for-the-badge&logo=buymeacoffee&logoColor=white&labelColor=4f4f4f)](https://wa.me/8801540221898?text=Hello%2C%20I%20want%20to%20support.%20How%20can%20I%20%3F)
