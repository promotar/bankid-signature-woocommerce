jQuery(document).ready(function ($) {
    let orderRef = '';
    const apiUser = bankid_vars.apiUser;
    const password = bankid_vars.password;
    const companyApiGuid = bankid_vars.companyApiGuid;
    const apiUrl = bankid_vars.apiUrl;

    function hidePaymentMethodsIfBankID() {
        if ($('#bankid-verification').length > 0) {
            $('#payment').hide();
        }
    }

    hidePaymentMethodsIfBankID();
    $(document.body).on('updated_checkout', function () {
        hidePaymentMethodsIfBankID();
    });

    $.post(bankid_vars.start_sign_url, {
        apiUser,
        password,
        companyApiGuid
    }, function (res) {
        if (res.apiCallResponse && res.apiCallResponse.Response) {
            orderRef = res.apiCallResponse.Response.OrderRef;

            // ✅ define autoToken and redirectUrl
            const autoToken = res.apiCallResponse.Response.AutoStartToken;
            const redirectUrl = window.location.href;

            function refreshQR(url) {
                $('#bankid-qr').attr('src', url + '?' + new Date().getTime());
            }

            // ✅ Add manual redirect button below QR
            const bankidLink = `https://app.bankid.com/?autostarttoken=${autoToken}&redirect=${encodeURIComponent(redirectUrl)}`;
            $('#bankid-verification').append(`
                <p style="margin-top: 10px;">
                    <a href="${bankidLink}" id="bankid-redirect-btn" class="button" style="display:inline-block;padding:10px 20px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;">
                        Open BankID App to Sign
                    </a>
                </p>
            `);

            // ✅ Refresh QR every 5 seconds
            refreshQR(res.apiCallResponse.Response.QrImage);
            setInterval(() => refreshQR(res.apiCallResponse.Response.QrImage), 5000);

            // ✅ Check signing status
            let interval = setInterval(() => {
                $.post(bankid_vars.check_status_url, {
                    apiUser,
                    password,
                    companyApiGuid,
                    orderRef
                }, function (status) {
                    if (status.apiCallResponse && status.apiCallResponse.Response.Status === 'complete') {
                        $('#bankid-status').text('Signature confirmed!');
                        clearInterval(interval);
                        $('<input>').attr({ type: 'hidden', name: 'bankid_verified', value: '1' }).appendTo('form.checkout');
                        $('form.checkout').submit();
                    } else if (status.apiCallResponse && status.apiCallResponse.Response.Status === 'failed') {
                        $('#bankid-status').css('color', 'red').text('Signature failed: ' + status.apiCallResponse.Response.HintCode);
                        clearInterval(interval);
                    }
                });
            }, 5000);
        } else {
            $('#bankid-status').css('color', 'red').text('❌ Failed to load BankID QR code. Please check API settings.');
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        let errorMsg = '❌ Network/API error while connecting to BankID.';
        if (jqXHR.responseText) {
            errorMsg += ` Server says: ${jqXHR.responseText}`;
        } else if (errorThrown) {
            errorMsg += ` (${errorThrown})`;
        } else {
            errorMsg += ` (Status: ${jqXHR.status} - ${textStatus})`;
        }
        $('#bankid-status').css('color', 'red').text(errorMsg);
    });
});
