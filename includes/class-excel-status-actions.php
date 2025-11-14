<?php
/**
 * Actions Class
 * Handles AJAX and form submissions for status updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class Excel_Status_Actions {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        // AJAX actions for single update
        add_action('wp_ajax_excel_status_single_update', array(__CLASS__, 'handle_single_update'));
        
        // AJAX actions for bulk update
        add_action('wp_ajax_excel_status_bulk_update', array(__CLASS__, 'handle_bulk_update'));
    }
    
    /**
     * Handle single order status update via AJAX
     */
    public static function handle_single_update() {
        // Check nonce
        check_ajax_referer('excel_status_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'excel-status')));
        }
        
        // Get parameters
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
        
        if (!$order_id || !$new_status) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'excel-status')));
        }
        
        // Remove 'wc-' prefix if present
        $new_status = str_replace('wc-', '', $new_status);
        
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'excel-status')));
        }
        
        // Update status
        $result = $order->update_status($new_status, __('Status updated via Excel Status Updater.', 'excel-status'), true);
        
        if ($result) {
            // Update the transient data with new status
            $transient_key = 'excel_status_orders_' . get_current_user_id();
            $orders_data = get_transient($transient_key);
            
            if ($orders_data && is_array($orders_data)) {
                // Update status in transient for this order
                foreach ($orders_data as $key => $order_data) {
                    if ($order_data['order_id'] == $order_id) {
                        $orders_data[$key]['status'] = $new_status;
                        break;
                    }
                }
                
                // Save updated data back to transient
                set_transient($transient_key, $orders_data, 3600);
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Order #%s status updated successfully.', 'excel-status'), $order->get_order_number()),
                'new_status' => wc_get_order_status_name($new_status),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update order status.', 'excel-status')));
        }
    }
    
    /**
     * Handle bulk order status update via AJAX
     */
    public static function handle_bulk_update() {
        // Check nonce
        check_ajax_referer('excel_status_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'excel-status')));
        }
        
        // Get parameters
        $order_ids = isset($_POST['order_ids']) ? array_map('absint', $_POST['order_ids']) : array();
        $new_status = isset($_POST['bulk_status']) ? sanitize_text_field($_POST['bulk_status']) : '';
        
        if (empty($order_ids) || !$new_status) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'excel-status')));
        }
        
        // Remove 'wc-' prefix if present
        $new_status = str_replace('wc-', '', $new_status);
        
        $updated_count = 0;
        $failed_count = 0;
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $result = $order->update_status($new_status, __('Status updated via Excel Status Updater (bulk action).', 'excel-status'), true);
                if ($result) {
                    $updated_count++;
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
        }
        
        // Update the transient data with new status for successfully updated orders
        if ($updated_count > 0) {
            $transient_key = 'excel_status_orders_' . get_current_user_id();
            $orders_data = get_transient($transient_key);
            
            if ($orders_data && is_array($orders_data)) {
                // Update status in transient for each successfully updated order
                foreach ($orders_data as $key => $order_data) {
                    if (in_array($order_data['order_id'], $order_ids)) {
                        // Verify the order was actually updated
                        $order = wc_get_order($order_data['order_id']);
                        if ($order && $order->get_status() === $new_status) {
                            $orders_data[$key]['status'] = $new_status;
                        }
                    }
                }
                
                // Save updated data back to transient
                set_transient($transient_key, $orders_data, 3600);
            }
        }
        
        if ($updated_count > 0) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully updated %d orders. %d failed.', 'excel-status'),
                    $updated_count,
                    $failed_count
                ),
                'updated_count' => $updated_count,
                'failed_count' => $failed_count,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update any orders.', 'excel-status')));
        }
    }
}

