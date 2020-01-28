<?php
/**
 * Plugin Name: Mercado Pago payments for WooCommerce
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: Configure the payment options and accept payments with cards, ticket and money of Mercado Pago account.
 * Version: 4.1.1
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/developers/
 * Text Domain: woocommerce-mercadopago
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 3.8.1
 *
 * @package MercadoPago
 * @category Core
 * @author Mercado Pago
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if ( ! defined( 'WC_MERCADOPAGO_BASENAME' ) ) {
    define( 'WC_MERCADOPAGO_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Load plugin text domain.
 *
 * Need to require here before test for PHP version.
 *
 * @since 3.0.1
 */
function woocommerce_mercadopago_load_plugin_textdomain()
{
    $text_domain = 'woocommerce-mercadopago';
    $locale = apply_filters( 'plugin_locale', get_locale(), $text_domain );
  
    $original_language_file = dirname(__FILE__) . '/i18n/languages/woocommerce-mercadopago-'. $locale .'.mo';
    
    // Unload the translation for the text domain of the plugin
    unload_textdomain($text_domain);
    // Load first the override file
    load_textdomain($text_domain, $original_language_file );
}
add_action( 'plugins_loaded', 'woocommerce_mercadopago_load_plugin_textdomain' );

/**
 * Notice about unsupported PHP version.
 *
 * @since 3.0.1
 */
function wc_mercado_pago_unsupported_php_version_notice()
{
    $type = 'error';
    $message = esc_html__('Mercado Pago payments for WooCommerce requires PHP version 5.6 or later. Please update your PHP version.', 'woocommerce-mercadopago');
    echo WC_WooMercadoPago_Configs::getAlertFrame($message, $type);
}

// Check for PHP version and throw notice.
if (version_compare(PHP_VERSION, '5.6', '<=')) {
    add_action('admin_notices', 'wc_mercado_pago_unsupported_php_version_notice');
    return;
}

/**
 * Curl validation
 */
function wc_mercado_pago_notify_curl_error()
{       
    $type = 'error';
    $message = __('Mercado Pago Error: PHP Extension CURL is not installed.', 'woocommerce-mercadopago');
    echo WC_WooMercadoPago_Configs::getAlertFrame($message, $type);
}

if (!in_array('curl', get_loaded_extensions())) {
        add_action('admin_notices', 'wc_mercado_pago_notify_curl_error');
        return;
}

/**
 * Summary: Places a warning error to notify user that WooCommerce is missing.
 * Description: Places a warning error to notify user that WooCommerce is missing.
 */
function notify_woocommerce_miss()
{
    $type = 'error';
    $message = sprintf(
            __('The payment module of Woo Mercado depends on the latest version of %s to run!', 'woocommerce-mercadopago'),
            ' <a href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>'
        );
    echo WC_WooMercadoPago_Configs::getAlertFrame($message, $type);
}

$all_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (!stripos(implode($all_plugins), 'woocommerce.php')) {
    add_action('admin_notices', 'notify_woocommerce_miss');
    return;
}

/**
 * Summary: Places a warning error to notify user that other older versions are active.
 * Description: Places a warning error to notify user that other older versions are active.
 * @since 3.0.7
 */
function wc_mercado_pago_notify_deprecated_presence()
{
    echo '<div class="error"><p>' .
        __('It seems that you already have the Mercado Pago module installed. Please uninstall it before using this version.', 'woocommerce-mercadopago') .
        '</p></div>';
}

// Check if previously versions are installed, as we can't let both operate.
if (class_exists('WC_WooMercadoPago_Module')) {
    add_action('admin_notices', 'wc_mercado_pago_notify_deprecated_presence');
    return;
}

// Load Mercado Pago SDK
require_once dirname(__FILE__) . '/includes/module/sdk/lib/MP.php';

// Load module class if it wasn't loaded yet.
if (!class_exists('WC_WooMercadoPago_Module'))
{
    require_once dirname(__FILE__) . '/includes/module/config/WC_WooMercadoPago_Constants.php';
    require_once dirname(__FILE__) . '/includes/module/WC_WooMercadoPago_Exception.php';
    require_once dirname(__FILE__) . '/includes/module/WC_WooMercadoPago_Configs.php';
    require_once dirname(__FILE__) . '/includes/module/log/WC_WooMercadoPago_Log.php';
    require_once dirname(__FILE__) . '/includes/module/WC_WooMercadoPago_Module.php';
    require_once dirname(__FILE__) . '/includes/module/WC_WooMercadoPago_Credentials.php';

    add_action('woocommerce_order_actions', 'add_mp_order_meta_box_actions');
    function add_mp_order_meta_box_actions($actions)
    {
        $actions['cancel_order'] = __('Cancel order', 'woocommerce-mercadopago');
        return $actions;
    }

    add_action('plugins_loaded', array('WC_WooMercadoPago_Module', 'init_mercado_pago_class'));
}
