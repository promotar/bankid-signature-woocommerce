// File: bankid-signature-woocommerce/assets/bankid-signature.js
// Handles displaying the BankID modal and communicating with the API
jQuery(document).ready(function ($) {
    // Entry point called from the payment page
    window.BankIDSignatureStart = function(orderId) {
        let orderRef = '', autoStartToken = '', qrImage = '', pollInterval, qrInterval;
        let modal = $('#bankid-signature-modal');

        // Detect whether the visitor is on a mobile device
        function isMobile() {
            return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        }
        // Build the BankID app link without automatic redirects
        function getBankIDAppLink(autoToken) {
            if (isMobile()) {
                return `https://app.bankid.com/?autostarttoken=${autoToken}`;
            } else {
                return `bankid:///?autostarttoken=${autoToken}`;
            }
        }
        // Periodically refresh the QR code image
        function startQRRefresh(qrUrl, imgSelector) {
            let elapsed = 0;
            function refreshQR() {
                let now = Date.now();
                let src = qrUrl + '?' + now;
                $(imgSelector).attr('src', src);
                elapsed += 5000;
                if (elapsed >= 300000) {
                    clearInterval(qrInterval);
                    $('#bankid-status').html('QR code expired. Please restart the process.');
                }
            }
            refreshQR();
            if (qrInterval) clearInterval(qrInterval);
            elapsed = 0;
            qrInterval = setInterval(refreshQR, 5000);
        }
        // Display the modal with provided HTML content
        function showModal(content) {
            modal.html(content);
            modal.show();
        }
        // Hide the modal and stop timers
        function hideModal() {
            modal.hide();
            if (qrInterval) clearInterval(qrInterval);
            if (pollInterval) clearInterval(pollInterval);
        }
        // Begin the signing process via AJAX
        $.post(bankid_vars.gateway_ajax, {
            step: 'start',
            order_id: orderId
        }, function (res) {
            if (res.success) {
                orderRef = res.data.OrderRef;
                autoStartToken = res.data.AutoStartToken;
                qrImage = res.data.QrImage;
                renderQRModal(qrImage, autoStartToken);
                pollInterval = setInterval(function(){ checkSignStatus(orderId); }, 3000);
            } else {
                showModal('<div style="color:red;text-align:center;">Error: ' + res.data.msg + '</div>');
            }
        });

        // Show the QR code modal and handle the "open app" button
        function renderQRModal(qrImg, autoToken) {
            let openAppUrl = getBankIDAppLink(autoToken);
            let modalHtml = `
            <div style="padding:20px;text-align:center">
                <div style="margin-bottom:10px;font-weight:bold;">Scan the QR or open the app to sign</div>
                <img src="${qrImg}" id="bankid-qr-img" style="width:200px;height:200px;border:1px solid #eee;"/>
                <br>
                <button id="bankid-open-app-btn" style="margin-top:15px;background:#003366;color:#fff;padding:8px 30px;border-radius:6px;border:none;font-size:16px;">OPEN BANKID APP</button>
                <div id="bankid-status" style="margin-top:15px;color:#333;"></div>
            </div>`;
            showModal(modalHtml);
            $('#bankid-open-app-btn').on('click', function () {
                window.location.href = openAppUrl;
            });
            startQRRefresh(qrImg, '#bankid-qr-img');
        }

        // Poll the API to check if the user has completed signing
        function checkSignStatus(orderId) {
            $.post(bankid_vars.gateway_ajax, {
                step: 'collect',
                order_id: orderId
            }, function (res) {
                if (res.success && res.data.status === 'complete') {
                    clearInterval(pollInterval);
                    clearInterval(qrInterval);
                    showModal('<div style="padding:30px;text-align:center;color:green;font-weight:bold;">Signed successfully.<br>Redirecting to thank you page...</div>');
                    setTimeout(function(){
                        if (res.data.thankyou_url) {
                            window.location.href = res.data.thankyou_url;
                        } else {
                            window.location.reload();
                        }
                    }, 3500);
                } else if (res.success && res.data.status === 'failed') {
                    clearInterval(pollInterval);
                    showModal('<div style="color:red;text-align:center;">Sign failed: ' + (res.data.hintCode || '') + '</div>');
                } else if (res.success) {
                    $('#bankid-status').html('Waiting for your signature...');
                }
            });
        }

        // Hide modal when clicking outside
        $(document).on('click', '#bankid-signature-modal', function (e) {
           if ($(e.target).is('#bankid-signature-modal')) hideModal();
       });
    }
});
