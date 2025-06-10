<?php
// File: bankid-signature-woocommerce/bankid-signature.php

/*
Plugin Name: BankID Signature for WooCommerce
Description: Electronic signature for WooCommerce using BankID. Adds a modal with QR code and app link for products that require it.
Production URL: https://layar1.com
Plugin URI: https://layar1.com
Version: 4.4
Author: Ziad Mansor
Author URI: https://jo-promoter.com
*/

if (!defined('ABSPATH')) exit;

define('BANKID_SIGNATURE_PLUGIN_PATH', plugin_dir_path(__FILE__));
require_once BANKID_SIGNATURE_PLUGIN_PATH . 'admin/settings-page.php';

// Register BankID as a payment gateway within WooCommerce
add_filter('woocommerce_payment_gateways', function($methods) {
    $methods[] = 'WC_Gateway_Bankid_Signature'; // add our custom gateway class
    return $methods;
});

add_action('plugins_loaded', function() {
    if (!class_exists('WC_Payment_Gateway')) return;

    /**
     * Payment gateway class responsible for handling the BankID signature flow
     * on the checkout page.
     */
    class WC_Gateway_Bankid_Signature extends WC_Payment_Gateway {
        /**
         * Setup gateway properties and hook required actions.
         */
        public function __construct() {
            $this->id                 = 'bankid_signature';
            $this->method_title       = __('BankID Signature', 'bankid');
            $this->method_description = __('You will be required to electronically sign your order using BankID. Once your signature is verified, your order will be processed and you will receive access to your purchased product or service. ', 'bankid');
            $this->description        = __('You will be required to electronically sign your order using BankID. Once your signature is verified, your order will be processed and you will receive access to your purchased product or service.', 'bankid');
            $this->title              = __('BankID Signature', 'bankid');
            $this->order_button_text  = __('Sign with BankID', 'bankid');
            $this->has_fields         = true;
            $this->supports           = ['products'];
            $this->enabled            = 'yes';

            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('woocommerce_receipt_' . $this->id, [$this, 'payment_page']);
            add_action('woocommerce_api_wc_gateway_' . $this->id, [$this, 'gateway_api']);
        }
        /**
         * Output the description and modal container on the checkout page.
         */
        public function payment_fields() {
            if ($this->description) {
                echo '<div class="bankid-description" style="margin-bottom:12px;color:#003366;background:#f9f9f9;padding:10px 16px;border-radius:5px;">'
                    . esc_html($this->description)
                    . '</div>';
            }
            echo '<div id="bankid-signature-modal" style="display:none;"></div>';
        }

        /**
         * Load scripts and styles required for the modal during checkout.
         */
        public function enqueue_scripts() {
            if (is_checkout() || is_wc_endpoint_url('order-pay')) {
                wp_enqueue_style('bankid-signature-style', plugin_dir_url(__FILE__).'assets/bankid-modal.css');
                wp_enqueue_script('bankid-signature-js', plugin_dir_url(__FILE__).'assets/bankid-signature.js', ['jquery'], false, true);
                wp_localize_script('bankid-signature-js', 'bankid_vars', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'apiUser' => get_option('bankid_apiUser'),
                    'password' => get_option('bankid_password'),
                    'companyApiGuid' => get_option('bankid_companyApiGuid'),
                    'api_url' => get_option('bankid_api_url'),
                    'gateway_ajax' => WC()->api_request_url('wc_gateway_bankid_signature'),
                ]);
            }
        }



        /**
         * Standard WooCommerce payment handler that redirects back to the
         * order payment page where the BankID process will start.
         */
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            return [
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            ];
        }

    /**
     * Output the modal container and automatically trigger the JS handler
     * on the order payment page.
     */
    public function payment_page($order_id) {
    ?>
    <div id="bankid-signature-modal" style="display:none;"></div>
     <div id="bankid-qr-overlay">
        <div>
            <div class="bankid-spinner"></div>
            <div class="bankid-qr-message">
                Please do not leave or refresh this page until signing is complete.<br>
                <b>Do not close or click outside this area!</b>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($){
        // Call the start function even if the user refreshed the page
        setTimeout(function(){
            if (typeof window.BankIDSignatureStart === "function") {
                window.BankIDSignatureStart(<?php echo intval($order_id); ?>);
            }
        }, 200);
    });
    </script>
    <?php
}


        /**
         * AJAX endpoint used by the JS script to start the signing process
         * and poll for its completion.
         */
        public function gateway_api() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') wp_send_json_error(['msg' => 'Invalid request']);

            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            $order = wc_get_order($order_id);
            if (!$order) wp_send_json_error(['msg' => 'Order not found']);

            // Step 1: initiate the BankID signing session
            if (!empty($_POST['step']) && $_POST['step'] === 'start') {
                $products = [];
                foreach ($order->get_items() as $item) {
                    $prodName = $item->get_name();
                    $sku = $item->get_product() ? $item->get_product()->get_sku() : '';
                    $qty = $item->get_quantity();
                    $products[] = "- {$prodName}" . ($sku ? " (SKU: {$sku})" : "") . " x {$qty}";
                }
                $visibleData =
                    "Order Date: ".date('Y-m-d')."\n" .
                    "Product(s):\n".implode("\n", $products)."\n".
                    "By signing, you confirm and approve the processing and installation of this order.";

                $post = [
                    "apiUser" => get_option('bankid_apiUser'),
                    "password" => get_option('bankid_password'),
                    "companyApiGuid" => get_option('bankid_companyApiGuid'),
                    "endUserIp" => $_SERVER['REMOTE_ADDR'],
                    "userVisibleData" => $visibleData,
                    "userVisibleDataFormat" => "simpleMarkdownV1",
                    "getQr" => true
                ];
                $response = wp_remote_post(get_option('bankid_api_url').'/api/sign', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => json_encode($post),
                    'timeout' => 30
                ]);
                if (is_wp_error($response)) wp_send_json_error(['msg' => 'API error']);
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['authResponse']['Success']) && !empty($body['apiCallResponse']['Response'])) {
                    $r = $body['apiCallResponse']['Response'];
                    update_post_meta($order_id, '_bankid_orderref', $r['OrderRef']);
                    wp_send_json_success([
                        'OrderRef' => $r['OrderRef'],
                        'AutoStartToken' => $r['AutoStartToken'],
                        'QrImage' => $r['QrImage']
                    ]);
                } else {
                    wp_send_json_error(['msg' => 'BankID API error']);
                }
            }

            // Step 2: collect the status of the signing session
            if (!empty($_POST['step']) && $_POST['step'] === 'collect') {
                $orderRef = get_post_meta($order_id, '_bankid_orderref', true);
                if (!$orderRef) wp_send_json_error(['msg' => 'Missing orderRef']);
                $post = [
                    "apiUser" => get_option('bankid_apiUser'),
                    "password" => get_option('bankid_password'),
                    "companyApiGuid" => get_option('bankid_companyApiGuid'),
                    "orderRef" => $orderRef
                ];
                $response = wp_remote_post(get_option('bankid_api_url').'/api/collectstatus', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => json_encode($post),
                    'timeout' => 20
                ]);
                if (is_wp_error($response)) wp_send_json_error(['msg' => 'API error']);
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['authResponse']['Success']) && !empty($body['apiCallResponse']['Response'])) {
                    $r = $body['apiCallResponse']['Response'];
                    if ($r['Status'] === 'complete') {
                        $order->payment_complete();
                        $order->add_order_note('Signed via BankID. Data: '.json_encode($r['CompletionData']['user']));
                        $thankyou_url = $order->get_checkout_order_received_url();
                        wp_send_json_success([
                            'status' => 'complete',
                            'thankyou_url' => $thankyou_url
                        ]);
                    } elseif ($r['Status'] === 'failed') {
                        $order->update_status('failed', __('BankID signature failed.', 'bankid'));
                        wp_send_json_success(['status' => 'failed', 'hintCode' => $r['HintCode']]);
                    } else {
                        wp_send_json_success(['status' => $r['Status'], 'hintCode' => $r['HintCode']]);
                    }
                } else {
                    wp_send_json_error(['msg' => 'BankID collect error']);
                }
            }

            wp_send_json_error(['msg' => 'Invalid step']);
        }
    }
});

// Display a button on the thank you page for products that require signing
add_action('woocommerce_thankyou', function($order_id){
    $order = wc_get_order($order_id);
    if (!$order) return;

    $has_bankid = false;
    $redirect_url = '';
    foreach ($order->get_items() as $item) {
        if (get_post_meta($item->get_product_id(), '_bankid_enabled', true) == 'yes') {
            $redirect_url = get_post_meta($item->get_product_id(), '_bankid_redirect_url', true);
            $has_bankid = true;
            break;
        }
    }

    if ($has_bankid && $redirect_url) {
        ?>
        <div id="bankid-receive-product-container" style="margin:30px 0 20px 0; text-align:center;">
            <a id="bankid-receive-btn" href="<?php echo esc_url($redirect_url); ?>" target="_blank"
                class="button"
                style="background:#26af5c;color:#fff;padding:10px 40px;font-size:18px;border-radius:6px;border:none;display:inline-block;">
                Receive Product
            </a>
            <div id="bankid-countdown" style="margin-top:10px;color:#003366;font-size:15px;font-weight:bold;">
                You will be redirected in <span id="bankid-seconds">7</span> seconds...
            </div>
        </div>
        <script>
        (function(){
            var counter = 7;
            var interval = setInterval(function(){
                counter--;
                document.getElementById('bankid-seconds').textContent = counter;
                if(counter <= 0){
                    clearInterval(interval);
            window.open("<?php echo esc_url($redirect_url); ?>", "_blank");
                }
            }, 1000);
        })();
        </script>
        <?php
    }
}, 2); // priority 2 so it appears before order details


// Add product level settings for enabling BankID
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_checkbox([
        'id' => '_bankid_enabled',
        'label' => 'BankID Signature',
        'description' => 'Enable BankID signature for this product'
    ]);
    woocommerce_wp_text_input([
        'id' => '_bankid_redirect_url',
        'label' => 'Redirect URL after signing',
        'description' => 'Customer will be redirected to this URL after successful signing',
        'desc_tip' => true,
    ]);
});
// Ensure only relevant gateways are shown when a BankID product is in the cart
add_filter('woocommerce_available_payment_gateways', function($gateways) {
    if (is_admin() || !is_checkout()) return $gateways;

    $has_bankid = false;

    if (WC()->cart && count(WC()->cart->get_cart())) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if (get_post_meta($product->get_id(), '_bankid_enabled', true) === 'yes') {
                $has_bankid = true;
                break;
            }
        }
    }

    // Show only the BankID gateway if any item requires it
    if ($has_bankid) {
        foreach ($gateways as $gateway_id => $gateway) {
            if ($gateway_id !== 'bankid_signature') {
                unset($gateways[$gateway_id]);
            }
        }
    } else {
        // Hide the gateway if no items need a signature
        if (isset($gateways['bankid_signature'])) {
            unset($gateways['bankid_signature']);
        }
    }
    return $gateways;
});

// Prevent mixing BankID products with regular ones in the cart
add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id, $quantity) {
    // Is the product being added signed with BankID?
    $is_bankid = get_post_meta($product_id, '_bankid_enabled', true) === 'yes';

    // Check if the cart already contains a BankID product
    $cart_has_bankid = false;
    foreach (WC()->cart->get_cart() as $item) {
        $pid = $item['product_id'];
        if (get_post_meta($pid, '_bankid_enabled', true) === 'yes') {
            $cart_has_bankid = true;
            break;
        }
    }

    // Validation rules
    // 1) Product requires signing while cart is not empty
    if ($is_bankid && WC()->cart->get_cart_contents_count() > 0) {
        wc_add_notice(__('You cannot add this product with other products in the cart. Please empty your cart first.', 'bankid'), 'error');
        return false;
    }

    // 2) Cart has a signature product and another product is added
    if (!$is_bankid && $cart_has_bankid) {
        wc_add_notice(__('You cannot add other products with a digital signature product. Please remove it from your cart first.', 'bankid'), 'error');
        return false;
    }

    // 3) Cart has other products and a signature product is being added
    if ($is_bankid && WC()->cart->get_cart_contents_count() > 0) {
        wc_add_notice(__('You cannot add this product with other products in the cart. Please empty your cart first.', 'bankid'), 'error');
        return false;
    }

    return $passed;
}, 10, 3);

// Save the custom product settings when a product is updated
add_action('woocommerce_process_product_meta', function($post_id) {
    update_post_meta($post_id, '_bankid_enabled', isset($_POST['_bankid_enabled']) ? 'yes' : 'no');
    if (isset($_POST['_bankid_redirect_url'])) {
        update_post_meta($post_id, '_bankid_redirect_url', esc_url_raw($_POST['_bankid_redirect_url']));
    }



});
