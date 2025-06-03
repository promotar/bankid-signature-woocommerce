# bankid-signature-woocommerce
start the BankID app
🔐 Plugin Overview: BankID Signature for WooCommerce
Plugin Name: BankID Signature for WooCommerce
Version: 2.8
Developer: Ziad Mansor
Website: https://jo-promoter.com
Company: Layar1
Company Website: https://layar1.com
Region of Operation: Jordan – MENA

🔎 What Does This Plugin Do?
This plugin provides seamless digital signature integration via BankID for WooCommerce-based stores. It enables merchants to enforce secure customer authentication and order confirmation using the official Swedish BankID app during the checkout process.

It’s specifically designed for scenarios where user verification is required before redirecting them to an external purchase, such as affiliate, telecom, legal, or financial services.

⚙️ How It Works
Adds a custom option in the product settings to activate BankID signature requirement per product.

If enabled, the customer is prompted at checkout to sign the order via the BankID app.

A QR code is displayed (desktop) or the app is auto-launched (mobile).

The plugin monitors signature status via BankID’s collectstatus API.

Once signed, the order is automatically processed and the user is redirected to a defined URL.

Payment methods are hidden and replaced by the BankID process to avoid any conflicts.

🧠 Key Features
✅ Supports all WooCommerce product types (simple, variable, affiliate…)
✅ Adds BankID sign option to each product (via checkbox)
✅ Accepts custom redirect URL after successful signature
✅ Dynamically shows QR Code or launches BankID app based on device
✅ Fully compliant with BankID signing workflow and security
✅ Stores a note in the order: BankID signed
✅ Removes traditional payment methods during the process
✅ Handles signature errors and timeouts gracefully
✅ Customizable from plugin settings page (API keys & endpoints)

🧑‍💼 About the Developer
Ziad Mansor is a full-stack web developer specialized in advanced Laravel and WordPress/WooCommerce systems. He is the technical lead at Layar1, a digital innovation company based in Jordan that offers web hosting, crypto platforms, telecom systems, and secure web applications.

🛡️ Ideal Use Cases
Telecom companies requiring secure identity confirmation

Affiliate marketing products that need signature verification

Legal or insurance services offering digital contract approval

Any WooCommerce store requiring verified purchases before redirection

