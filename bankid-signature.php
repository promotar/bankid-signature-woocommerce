<?php
/*
Plugin Name: BankID Signature for WooCommerce
Description: Forces signature via BankID for products that require it.
Production URL: https://layar1.com
Plugin URI: https://layar1.com
Version: 3.1
Author: Ziad Mansor
Author URI: https://jo-promoter.com
*/

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    if (is_checkout()) {
        wp_enqueue_script('bankid-checkout', plugin_dir_url(__FILE__) . 'assets/js/bankid-checkout.js', ['jquery'], false, true);
        wp_localize_script('bankid-checkout', 'bankid_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'start_sign_url' => esc_url(admin_url('admin-ajax.php?action=start_bankid')),
            'check_status_url' => esc_url(admin_url('admin-ajax.php?action=check_bankid_status')),
            'apiUser' => get_option('bankid_apiUser'),
            'password' => get_option('bankid_password'),
            'companyApiGuid' => get_option('bankid_companyApiGuid'),
            'apiUrl' => get_option('bankid_apiUrl'),
        ]);
    }
});

add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
        'id' => '_bankid_required',
        'label' => __('BankID sign', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Require BankID signature to purchase this product.')
    ]);
    woocommerce_wp_text_input([
        'id' => '_bankid_redirect_url',
        'label' => __('Redirect URL after signing', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('URL to redirect after signing'),
        'type' => 'url'
    ]);
    echo '</div>';
});

add_action('woocommerce_process_product_meta', function ($post_id) {
    update_post_meta($post_id, '_bankid_required', isset($_POST['_bankid_required']) ? 'yes' : 'no');
    update_post_meta($post_id, '_bankid_redirect_url', esc_url_raw($_POST['_bankid_redirect_url']));
});

add_action('woocommerce_review_order_before_payment', function () {
    foreach (WC()->cart->get_cart() as $item) {
        if (get_post_meta($item['product_id'], '_bankid_required', true) === 'yes') {
            echo '<div id="bankid-verification">
                    <p>Sign via BankID to complete your order:</p>
                    <img id="bankid-qr" src="" style="max-width:200px;">
                    <p id="bankid-status" style="color:green;"></p>
                  </div>';
            break;
        }
    }
});

add_action('woocommerce_checkout_process', function () {
    foreach (WC()->cart->get_cart() as $item) {
        if (get_post_meta($item['product_id'], '_bankid_required', true) === 'yes' && empty($_POST['bankid_verified'])) {
            wc_add_notice(__('You must complete BankID signature to place the order.'), 'error');
            break;
        }
    }
});

add_action('woocommerce_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);
    foreach ($order->get_items() as $item) {
        if (get_post_meta($item->get_product_id(), '_bankid_required', true) === 'yes') {
            $order->update_status('processing');
            $order->add_order_note('BankID signed');
            $url = get_post_meta($item->get_product_id(), '_bankid_redirect_url', true);
            if ($url) {
                echo "<script>window.location.href='{$url}';</script>";
            }
        }
    }
});

add_filter('woocommerce_checkout_posted_data', function($data) {
    foreach (WC()->cart->get_cart() as $item) {
        if (get_post_meta($item['product_id'], '_bankid_required', true) === 'yes') {
            $data['payment_method'] = 'bankid_sign';
        }
    }
    return $data;
});

add_action('woocommerce_checkout_create_order', function($order, $data) {
    foreach ($order->get_items() as $item) {
        if (get_post_meta($item->get_product_id(), '_bankid_required', true) === 'yes') {
            $order->set_payment_method('BankID sign');
            $order->set_payment_method_title('BankID sign');
        }
    }
}, 10, 2);

add_action('admin_menu', function () {
    add_menu_page('BankID Settings', 'BankID Settings', 'manage_options', 'bankid-settings', 'render_bankid_settings');
});

function render_bankid_settings() {
    ?>
    <div class="wrap">
        <h1>BankID API Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('bankid_options_group');
            do_settings_sections('bankid-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('bankid_options_group', 'bankid_apiUser');
    register_setting('bankid_options_group', 'bankid_password');
    register_setting('bankid_options_group', 'bankid_companyApiGuid');
    register_setting('bankid_options_group', 'bankid_apiUrl');
 
    add_settings_section('bankid_main', 'API Configuration', null, 'bankid-settings');

    add_settings_field('bankid_apiUser', 'API User', function () {
        echo '<input type="text" name="bankid_apiUser" value="' . esc_attr(get_option('bankid_apiUser')) . '" class="regular-text">';
    }, 'bankid-settings', 'bankid_main');

    add_settings_field('bankid_password', 'Password', function () {
        echo '<input type="text" name="bankid_password" value="' . esc_attr(get_option('bankid_password')) . '" class="regular-text">';
    }, 'bankid-settings', 'bankid_main');

    add_settings_field('bankid_companyApiGuid', 'Company API GUID', function () {
        echo '<input type="text" name="bankid_companyApiGuid" value="' . esc_attr(get_option('bankid_companyApiGuid')) . '" class="regular-text">';
    }, 'bankid-settings', 'bankid_main');

    add_settings_field('bankid_apiUrl', 'API URL', function () {
        echo '<input type="text" name="bankid_apiUrl" value="' . esc_attr(get_option('bankid_apiUrl')) . '" class="regular-text">';
    }, 'bankid-settings', 'bankid_main');
});

add_action('wp_ajax_start_bankid', 'start_bankid');
add_action('wp_ajax_nopriv_start_bankid', 'start_bankid');

function start_bankid() {
    $payload = [
        'apiUser' => get_option('bankid_apiUser'),
        'password' => get_option('bankid_password'),
        'companyApiGuid' => get_option('bankid_companyApiGuid'),
        'endUserIp' => $_SERVER['REMOTE_ADDR'],
        'userVisibleData' => 'Sign to confirm order',
        'userNonVisibleData' => 'Order verification',
        'getQr' => true
    ];

    $api_url = get_option('bankid_apiUrl');
    if (!$api_url) {
        wp_send_json_error(['message' => 'API URL is missing'], 400);
    }

    error_log('BANKID SIGN PAYLOAD: ' . json_encode($payload));

    $response = wp_remote_post($api_url . '/sign', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($payload)
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Request failed', 'error' => $response->get_error_message()], 500);
    }

    wp_send_json(json_decode(wp_remote_retrieve_body($response), true));
}

