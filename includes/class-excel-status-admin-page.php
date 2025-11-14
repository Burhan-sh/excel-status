<?php
/**
 * Admin Page Class
 * Handles the admin menu and page display
 */

if (!defined('ABSPATH')) {
    exit;
}

class Excel_Status_Admin_Page {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'handle_file_upload'));
        add_action('admin_init', array(__CLASS__, 'handle_settings_save'));
        add_filter('set-screen-option', array(__CLASS__, 'set_screen_option'), 10, 3);
    }
    
    /**
     * Add admin menu under WooCommerce
     */
    public static function add_admin_menu() {
        $hook = add_submenu_page(
            'woocommerce',
            __('Order Status Updater', 'excel-status'),
            __('Status Updater', 'excel-status'),
            'manage_woocommerce',
            'excel-status-updater',
            array(__CLASS__, 'render_admin_page')
        );
        
        // Add screen options
        add_action("load-{$hook}", array(__CLASS__, 'add_screen_options'));
    }
    
    /**
     * Add screen options for per page setting
     */
    public static function add_screen_options() {
        $option = 'per_page';
        $args = array(
            'label' => __('Orders per page', 'excel-status'),
            'default' => 20,
            'option' => 'orders_per_page'
        );
        add_screen_option($option, $args);
    }
    
    /**
     * Save screen options
     */
    public static function set_screen_option($status, $option, $value) {
        if ('orders_per_page' === $option) {
            return $value;
        }
        return $status;
    }
    
    /**
     * Handle file upload
     */
    public static function handle_file_upload() {
        if (!isset($_POST['excel_status_upload_submit'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'excel_status_upload')) {
            wp_die(__('Security check failed.', 'excel-status'));
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'excel-status'));
        }
        
        // Check if file was uploaded
        if (empty($_FILES['excel_status_file'])) {
            add_action('admin_notices', function() {
                echo '<div class="error notice"><p>' . __('Please select a file to upload.', 'excel-status') . '</p></div>';
            });
            return;
        }
        
        // Import file
        $result = Excel_Status_Importer::import_file($_FILES['excel_status_file']);
        
        if (is_wp_error($result)) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="error notice"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            });
            return;
        }
        
        // Store results in transient
        set_transient(self::get_transient_key(), $result, 3600); // 1 hour
        
        add_action('admin_notices', function() use ($result) {
            echo '<div class="updated notice"><p>' . sprintf(
                __('Successfully imported! Found %d matching orders.', 'excel-status'),
                count($result)
            ) . '</p></div>';
        });
    }
    
    /**
     * Handle settings save
     */
    public static function handle_settings_save() {
        if (!isset($_POST['excel_status_settings_submit'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'excel_status_settings')) {
            wp_die(__('Security check failed.', 'excel-status'));
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'excel-status'));
        }
        
        // Save tracking meta key
        if (isset($_POST['tracking_meta_key'])) {
            $tracking_meta_key = sanitize_text_field($_POST['tracking_meta_key']);
            update_option('excel_status_tracking_meta_key', $tracking_meta_key);
            
            add_action('admin_notices', function() {
                echo '<div class="updated notice"><p>' . __('Settings saved successfully.', 'excel-status') . '</p></div>';
            });
        }
    }
    
    /**
     * Render admin page
     */
    public static function render_admin_page() {
        // Get orders from transient
        $orders = get_transient(self::get_transient_key());
        if (!$orders) {
            $orders = array();
        }
        
        ?>
        <div class="wrap excel-status-wrap">
            <h1><?php echo esc_html__('Order Status Updater', 'excel-status'); ?></h1>
            
            <div class="excel-status-container">
                <!-- Upload Form -->
                <div class="excel-status-upload-section">
                    <h2><?php esc_html_e('Upload CSV/XLSX File', 'excel-status'); ?></h2>
                    <form method="post" enctype="multipart/form-data" class="excel-status-upload-form">
                        <?php wp_nonce_field('excel_status_upload'); ?>
                        <p class="description">
                            <?php esc_html_e('Upload a CSV or XLSX file containing tracking numbers. The file should have a column with "tracking" in the header.', 'excel-status'); ?>
                        </p>
                        <input type="file" name="excel_status_file" accept=".csv,.xlsx,.xls" required />
                        <button type="submit" name="excel_status_upload_submit" class="button button-primary">
                            <?php esc_html_e('Upload & Import', 'excel-status'); ?>
                        </button>
                    </form>
                </div>
                
                <!-- Settings Form -->
                <div class="excel-status-settings-section">
                    <h2><?php esc_html_e('Settings', 'excel-status'); ?></h2>
                    <form method="post" class="excel-status-settings-form">
                        <?php wp_nonce_field('excel_status_settings'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="tracking_meta_key"><?php esc_html_e('Tracking Meta Key', 'excel-status'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="text" 
                                        id="tracking_meta_key"
                                        name="tracking_meta_key" 
                                        value="<?php echo esc_attr(get_option('excel_status_tracking_meta_key', '_rj_indiapost_tracking_number')); ?>" 
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('The meta key where tracking numbers are stored in WooCommerce orders. Default: _rj_indiapost_tracking_number', 'excel-status'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <button type="submit" name="excel_status_settings_submit" class="button">
                            <?php esc_html_e('Save Settings', 'excel-status'); ?>
                        </button>
                    </form>
                </div>
                
                <!-- Orders Table -->
                <?php if (!empty($orders)) : ?>
                <div class="excel-status-orders-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h2 style="margin: 0;"><?php esc_html_e('Matched Orders', 'excel-status'); ?></h2>
                        <button type="button" id="excel-status-reset-data" class="button button-secondary" style="background: #dc3232; color: white; border-color: #dc3232;">
                            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 2px;"></span>
                            <?php esc_html_e('Clear All Data', 'excel-status'); ?>
                        </button>
                    </div>
                    <form method="post" id="excel-status-orders-form">
                        <?php wp_nonce_field('excel_status_update'); ?>
                        <?php
                        $list_table = new Excel_Status_List_Table($orders);
                        $list_table->prepare_items();
                        $list_table->display();
                        ?>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get transient key for current user
     * 
     * @return string
     */
    private static function get_transient_key() {
        return 'excel_status_orders_' . get_current_user_id();
    }
    
    /**
     * Render status options for dropdown
     * 
     * @param string $current Current selected status
     */
    public static function render_status_options($current = '') {
        if (!function_exists('wc_get_order_statuses')) {
            return;
        }
        
        $statuses = wc_get_order_statuses();
        foreach ($statuses as $status_key => $status_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($status_key),
                selected($current, $status_key, false),
                esc_html($status_label)
            );
        }
    }
}
