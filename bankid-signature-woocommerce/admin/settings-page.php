<?php
add_action('admin_menu', function () {
    add_menu_page('BankID Settings', 'BankID Settings', 'manage_options', 'bankid-settings', 'bankid_settings_page');
});
 
function bankid_settings_page() {
    if (isset($_POST['bankid_settings'])) {
        update_option('bankid_apiUser', sanitize_text_field($_POST['api_user']));
        update_option('bankid_password', sanitize_text_field($_POST['api_password']));
        update_option('bankid_companyApiGuid', sanitize_text_field($_POST['company_guid'])); 
        update_option('bankid_api_url', esc_url_raw($_POST['api_url']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $api_user      = get_option('bankid_apiUser', '');
    $api_password  = get_option('bankid_password', '');
    $company_guid  = get_option('bankid_companyApiGuid', '');
    $api_url       = get_option('bankid_api_url', '');

    echo '<div class="wrap"><h1>BankID API Settings</h1><form method="post">
        <input type="hidden" name="bankid_settings" value="1" />
        <table class="form-table">
            <tr><th scope="row">API User</th><td><input type="text" name="api_user" value="' . esc_attr($api_user) . '" class="regular-text"></td></tr>
            <tr><th scope="row">API Password</th><td><input type="text" name="api_password" value="' . esc_attr($api_password) . '" class="regular-text"></td></tr>
            <tr><th scope="row">Company API GUID</th><td><input type="text" name="company_guid" value="' . esc_attr($company_guid) . '" class="regular-text"></td></tr>
            <tr><th scope="row">API URL</th><td><input type="text" name="api_url" value="' . esc_attr($api_url) . '" class="regular-text" /></td></tr>
        </table>
        <p class="submit"><input type="submit" class="button-primary" value="Save Changes"></p>
    </form></div>';
}
?>