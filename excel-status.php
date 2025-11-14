<?php
/**
 * Plugin Name: Excel Order Status Updater
 * Plugin URI: https://rajuplastics.com
 * Description: Import CSV/XLSX files with tracking numbers and bulk update WooCommerce order statuses
 * Version: 1.1.0
 * Author: Raju Plastics
 * Author URI: https://rajuplastics.com
 * Text Domain: excel-status
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EXCEL_STATUS_VERSION', '1.0.0');
define('EXCEL_STATUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EXCEL_STATUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXCEL_STATUS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function excel_status_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        // Deactivate the plugin
        deactivate_plugins(EXCEL_STATUS_PLUGIN_BASENAME);
        
        // Show error message
        add_action('admin_notices', function() {
            ?>
            <div class="error notice">
                <p>
                    <strong><?php esc_html_e('Excel Order Status Updater', 'excel-status'); ?></strong>
                    <?php esc_html_e('requires WooCommerce to be installed and active. The plugin has been deactivated.', 'excel-status'); ?>
                </p>
            </div>
            <?php
        });
        
        return false;
    }
    return true;
}

/**
 * Plugin activation hook
 */
function excel_status_activate() {
    if (!excel_status_check_woocommerce()) {
        wp_die(
            __('This plugin requires WooCommerce to be installed and active.', 'excel-status'),
            __('Plugin Activation Error', 'excel-status'),
            array('back_link' => true)
        );
    }
    
    // Set default options
    if (!get_option('excel_status_tracking_meta_key')) {
        update_option('excel_status_tracking_meta_key', '_rj_indiapost_tracking_number');
    }
}
register_activation_hook(__FILE__, 'excel_status_activate');

/**
 * Check WooCommerce on every admin page load
 */
add_action('admin_init', 'excel_status_check_woocommerce');

/**
 * Load plugin text domain for translations
 */
function excel_status_load_textdomain() {
    load_plugin_textdomain('excel-status', false, dirname(EXCEL_STATUS_PLUGIN_BASENAME) . '/languages');
}
add_action('plugins_loaded', 'excel_status_load_textdomain');

/**
 * Include required files
 */
function excel_status_init() {
    // Only load if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // Load vendor autoload for PhpSpreadsheet
    // First try theme's library (to avoid duplicate autoloader conflict)
    $theme_vendor = get_stylesheet_directory() . '/excel_library/vendor/autoload.php';
    $plugin_vendor = EXCEL_STATUS_PLUGIN_DIR . 'vendor/autoload.php';
    
    if (file_exists($theme_vendor) && !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        require_once $theme_vendor;
    } elseif (file_exists($plugin_vendor) && !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        require_once $plugin_vendor;
    }
    
    // Load WP_List_Table if not already loaded
    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }
    
    // Include plugin classes
    require_once EXCEL_STATUS_PLUGIN_DIR . 'includes/class-excel-status-importer.php';
    require_once EXCEL_STATUS_PLUGIN_DIR . 'includes/class-excel-status-list-table.php';
    require_once EXCEL_STATUS_PLUGIN_DIR . 'includes/class-excel-status-admin-page.php';
    require_once EXCEL_STATUS_PLUGIN_DIR . 'includes/class-excel-status-actions.php';
    
    // Initialize classes
    Excel_Status_Admin_Page::init();
    Excel_Status_Actions::init();
}
add_action('plugins_loaded', 'excel_status_init');

/**
 * Enqueue admin scripts and styles
 */
function excel_status_enqueue_admin_assets($hook) {
    // Only load on our plugin page
    if ('woocommerce_page_excel-status-updater' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'excel-status-admin',
        EXCEL_STATUS_PLUGIN_URL . 'assets/admin.css',
        array(),
        EXCEL_STATUS_VERSION
    );
    
    wp_enqueue_script(
        'excel-status-admin',
        EXCEL_STATUS_PLUGIN_URL . 'assets/admin.js',
        array('jquery'),
        EXCEL_STATUS_VERSION,
        true
    );
    
    wp_localize_script('excel-status-admin', 'excelStatusData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('excel_status_nonce'),
        'confirmBulk' => __('Are you sure you want to update the status of selected orders?', 'excel-status'),
        'selectOrders' => __('Please select at least one order.', 'excel-status'),
        'selectStatus' => __('Please select a status.', 'excel-status'),
    ));
}
add_action('admin_enqueue_scripts', 'excel_status_enqueue_admin_assets');

