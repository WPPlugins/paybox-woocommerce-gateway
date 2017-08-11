<?php

/**
 * Plugin Name: WooCommerce Paybox Payment gateway
 * Description: WooCommerce gateway payment plugin for Paybox
 * Version: 1.0.0.1
 * Author: Paybox Verifone
 * Author URI: http://www.paybox.com
 * 
 * @package WordPress
 */
// Ensure not called directly
if (!defined('ABSPATH')) {
    exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
include_once( ABSPATH . 'wp-admin/includes/template.php' );

if (!defined('PAYBOX_INCLUDES_PATH'))
    define('PAYBOX_INCLUDES_PATH', WP_PLUGIN_DIR . '/paybox-by-verifone-integration/includes/');
define('WC_PAYBOX_PLUGIN', 'woocommerce-paybox');
define('WC_PAYBOX_VERSION', '1.0.0');

if (!defined('PAYBOX_KEY_PATH'))
    define('PAYBOX_KEY_PATH', ABSPATH . 'kek.php');

function install_generic_plugin($plugin){
    // http://p.gj.d56.bm-services.com/wp-admin/update.php?action=install-plugin&plugin=paybox-by-verifone-integration&_wpnonce=9f01081ce0
    if(!function_exists('wp_get_current_user')) {
        include(ABSPATH . "wp-includes/pluggable.php"); 
    }
    if ( ! current_user_can('install_plugins') ){
        wp_die( __( 'You do not have sufficient permissions to install plugins on this site.' ) );
    }

        include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..

        // check_admin_referer( 'install-plugin_' . $plugin );
        $api = plugins_api( 'plugin_information', array(
            'slug' => $plugin,
            'fields' => array(
                'short_description' => false,
                'sections' => false,
                'requires' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'added' => false,
                'tags' => false,
                'compatibility' => false,
                'homepage' => false,
                'donate_link' => false,
                ),
            ) );

        if ( is_wp_error( $api ) ) {
            wp_die( $api );
        }

        // $title = __('Plugin Install');
        // $parent_file = 'plugins.php';
        // $submenu_file = 'plugin-install.php';
        // require_once(ABSPATH . 'wp-admin/admin-header.php');

        $title = sprintf( __('Installing Plugin: %s'), $api->name . ' ' . $api->version );
        $nonce = 'install-plugin_' . $plugin;
        $url = 'update.php?action=install-plugin&plugin=' . urlencode( $plugin );
        if ( isset($_GET['from']) )
            $url .= '&from=' . urlencode(stripslashes($_GET['from']));

        $type = 'web'; //Install plugin type, From Web or an Upload.
        include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        include_once( ABSPATH . 'wp-admin/includes/file.php' );
        include_once( ABSPATH . 'wp-admin/includes/misc.php' );
        $upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
        // $upgrader->install($api->download_link);

        return $upgrader->install($api->download_link);

    }

    function check_paybox_states_woo() {
        $this_plugin = get_plugin_data(__FILE__);
        $plugins = array(
            'paybox-by-verifone-integration' => array(
                'file_path' => 'paybox-by-verifone-integration/paybox-by-verifone-integration.php',
                'required' => true,
                'name' => 'Wordpress Paybox Payment plugin',
                'slug' => 'paybox-by-verifone-integration',
                'author' => 'Paybox Verifone',
                'depend' => '',
            // 'external_url' => 'http://www1.paybox.com/espace-integrateur-documentation/modules-by-paybox/',
                'wordpress_org_name' => 'paybox-by-verifone-integration',
                'source' => false,
                ),
            );
        $installed_plugins = get_plugins();

        if (!isset($installed_plugins[$plugins['paybox-by-verifone-integration']['file_path']])) {
        //On tente d installer le plugin generique automatiquement

            if(install_generic_plugin($plugins['paybox-by-verifone-integration']['slug'])){

            }else{
        $external_url = $plugins['paybox-by-verifone-integration']['external_url'];
                    $source = $plugins['paybox-by-verifone-integration']['source'];
                    $link = '';
                    if ($external_url && preg_match('|^http(s)?://|', $external_url)) {
                        $link = '<a href="' . esc_url($external_url) . '" title="' . $plugins['paybox-by-verifone-integration']['name'] . '" target="_blank">' . $plugins['paybox-by-verifone-integration']['name'] . '</a>';
                    } elseif (!$source || preg_match('|^http://wordpress.org/extend/plugins/|', $source)) {
                        $url = add_query_arg(
                            array(
                                'tab' => 'plugin-information',
                                'plugin' => $plugins['paybox-by-verifone-integration']['wordpress_org_name'],
                                'TB_iframe' => 'true',
                                'width' => '640',
                                'height' => '500',
                                ), network_admin_url('plugin-install.php')
                            );

                        $link = '<a href="' . esc_url($url) . '" class="thickbox" title="' . $plugins['paybox-by-verifone-integration']['name'] . '">' . $plugins['paybox-by-verifone-integration']['name'] . '</a>';
                    } else {
                    $link = $plugins['paybox-by-verifone-integration']['name']; // No hyperlink.
                }

                $message = '<i>' . $this_plugin['Name'] . '</i>' . ' requiert le plugin suivant : ' . $link;
                add_settings_error('paybox-wc', 'paybox-wc', $message, 'error');
                settings_errors('paybox-wc');
                return false;
            }
            
    }

    if (is_plugin_inactive($plugins['paybox-by-verifone-integration']['file_path'])) {
        require_once(PAYBOX_INCLUDES_PATH . 'paybox-helper.php');
        $plugins = Paybox_Helper::getPluginGeneric();
        Paybox_Helper::notices($plugins);
        return false;
    }

    return true;
}

function woocommerce_paybox_installation() {
    global $wpdb;
    $installed_ver = get_option("WC_PAYBOX_PLUGIN.'_version'");

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        __('WooCommerce must be activated', 'paybox');
        $rendered = '';
        add_settings_error('paybox', 'paybox', $rendered, 'rerere');
        die();
    }

    if ($installed_ver != WC_PAYBOX_VERSION) {
        $tableName = $wpdb->prefix . 'wc_paybox_payment';
        $sql = "CREATE TABLE $tableName (
        id int not null auto_increment,
        order_id bigint not null,
        type enum('capture', 'first_payment', 'second_payment', 'third_payment') not null,
        data varchar(2048) not null,
        KEY order_id (order_id),
        PRIMARY KEY  (id))";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option(WC_PAYBOX_PLUGIN . '_version', WC_PAYBOX_VERSION);
    }
}

function woocommerce_paybox_initialization() {
    $class = 'WC_Paybox_Abstract_Gateway';

    if (!class_exists($class)) {
        require_once(PAYBOX_INCLUDES_PATH . 'paybox-config.php');
        require_once(PAYBOX_INCLUDES_PATH . 'paybox-iso4217currency.php');
        require_once(PAYBOX_INCLUDES_PATH . 'payboxclass.php');

        require_once(plugin_dir_path(__FILE__) . '/includes/wc-paybox-abstract-gateway.php');
        require_once(plugin_dir_path(__FILE__) . '/includes/wc-paybox-standard-gateway.php');
        require_once(plugin_dir_path(__FILE__) . '/includes/wc-paybox-threetime-gateway.php');

        require_once(PAYBOX_INCLUDES_PATH . '/paybox-encrypt.php');
    }

    load_plugin_textdomain('paybox', false, 'lang');

    $crypto = new PayboxEncrypt();
    if (!file_exists(PAYBOX_KEY_PATH))
        $crypto->generateKey();

    if (get_site_option(WC_PAYBOX_PLUGIN . '_version') != WC_PAYBOX_VERSION) {
        woocommerce_paybox_installation();
    }
}

function woocommerce_paybox_register(array $methods) {
    $methods[] = 'WC_Paybox_Standard_Gateway';
    $methods[] = 'WC_Paybox_Threetime_Gateway';
    return $methods;
}

register_activation_hook(__FILE__, 'woocommerce_paybox_installation');

function woocommerce_paybox_show_details(WC_Order $order) {
    $method = get_post_meta($order->id, '_payment_method', true);
    switch ($method) {
        case 'standard':
        $method = new WC_Paybox_Standard_Gateway();
        $method->showDetails($order);
        break;
        case 'threetime':
        $method = new WC_Paybox_Threetime_Gateway();
        $method->showDetails($order);
        break;
    }
}

if (check_paybox_states_woo()) {
    add_action('plugins_loaded', 'woocommerce_paybox_initialization');
    add_filter('woocommerce_payment_gateways', 'woocommerce_paybox_register');

    add_action('woocommerce_admin_order_data_after_billing_address', 'woocommerce_paybox_show_details');
}

