<?php
/**
 * Plugin Name: Firebase Integration
 * Description: Integrates Firebase Firestore into WordPress with secure credential handling.
 * Version: 1.0
 * Author: Your Name
 */

defined('ABSPATH') or die('No script kiddies please!');

// Autoload Composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

// Admin initialization for setting registration
function firestore_integration_admin_init() {
    register_setting('firestore_integration_settings', 'firestore_credentials', 'firestore_credentials_sanitize');
    add_settings_section('firestore_settings_section', 'Firebase Settings', 'firestore_settings_section_cb', 'firestore_integration');
    add_settings_field('firestore_credentials_field', 'Firebase Credentials', 'firestore_credentials_field_cb', 'firestore_integration', 'firestore_settings_section');
}

function firestore_settings_section_cb() {
    echo '<p>Enter your Firebase credentials here.</p>';
}

function firestore_credentials_field_cb() {
    $credentials = get_option('firestore_credentials');
    echo '<textarea id="firestore_credentials" name="firestore_credentials" rows="10" cols="50" class="large-text">' . esc_textarea($credentials) . '</textarea>';
}

// Sanitize and encrypt input data
function firestore_credentials_sanitize($input) {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }
    return encrypt_data($input);
}

// Add menu item to the settings menu
function firestore_integration_admin_menu() {
    add_options_page('Firebase Integration Settings', 'Firebase Integration', 'manage_options', 'firebase-integration', 'firestore_integration_options_page');
}

add_action('admin_init', 'firestore_integration_admin_init');
add_action('admin_menu', 'firestore_integration_admin_menu');

// Options page rendering
function firestore_integration_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h1>Firebase Integration Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('firestore_integration_settings');
            do_settings_sections('firestore_integration');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Encrypt data
function encrypt_data($data) {
    $key = openssl_digest(wp_salt(), 'SHA256', TRUE);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Decrypt data
function decrypt_data($data) {
    $data = base64_decode($data);
    $key = openssl_digest(wp_salt(), 'SHA256', TRUE);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

// Get Firestore instance
function get_firestore_instance() {
    $credentials = json_decode(decrypt_data(get_option('firestore_credentials')), true);
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => $credentials['project_id'],
        'keyfile' => $credentials
    ]);
    return $firestore;
}
