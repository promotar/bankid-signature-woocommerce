# 🔐 BankID Signature for WooCommerce

**Version:** 2.8  
**Author:** [Ziad Mansor](https://jo-promoter.com)  
**Company:** [Layar1](https://layar1.com)  
**Region:** Jordan – MENA  

## 📝 Description

BankID Signature for WooCommerce is a powerful plugin that enables **secure digital signing** of WooCommerce orders via the Swedish **BankID** system.

It is ideal for telecom, financial, legal, or affiliate services that require **identity verification and user consent** before redirecting users to external URLs or completing sensitive orders.

---

## ⚙️ How It Works

- Adds a **"BankID Sign" checkbox** to every product edit page.
- If enabled, a **custom redirect URL field** appears (e.g., external purchase page).
- At checkout, if such a product is in cart:
  - The **BankID QR code** is displayed (for desktop), or
  - The **BankID app** is launched (on mobile).
- The plugin polls BankID every 5 seconds to check signing status.
- When signed successfully:
  - The order status becomes `processing`
  - A note is added: `BankID signed`
  - The customer is redirected to the provided URL.
- If signature fails or is cancelled:
  - An error message is shown to the user.

---

## 🧠 Features

✅ Supports **all WooCommerce product types**  
✅ Adds **BankID sign requirement** per product  
✅ Custom **redirect URL** after signing  
✅ Automatically detects device type (mobile vs desktop)  
✅ Hides WooCommerce payment methods during BankID flow  
✅ Full status monitoring via BankID `collectstatus` API  
✅ Admin settings page to manage **API keys and endpoint URLs**  
✅ Fully **localized and extensible**

---

## 🏢 About Layar1 & Developer

**Ziad Mansor** is a full-stack developer specialized in Laravel and WordPress ecosystems.  
He leads development at [**Layar1**](https://layar1.com), a company offering:

- Advanced hosting & SaaS platforms  
- Blockchain and crypto-based apps  
- Telecom & digital signature integrations  
- WooCommerce & API-based automation systems  

---

## 📦 Installation

1. Upload the plugin `.zip` via WordPress Admin > Plugins > Add New.
2. Activate the plugin.
3. Go to **WooCommerce > Settings > BankID** tab to enter your API credentials.
4. Edit any product and enable **BankID Sign** + define a **Redirect URL**.
5. Done! ✅

---

## 📄 License

MIT – Free to use and extend with attribution.
