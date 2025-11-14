<?php
/**
 * Excel/CSV Importer Class
 * Handles file upload and parsing
 */

if (!defined('ABSPATH')) {
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel_Status_Importer {
    
    /**
     * Import and process uploaded file
     * 
     * @param array $file Uploaded file data from $_FILES
     * @return array|WP_Error Array of tracking numbers or error
     */
    public static function import_file($file) {
        // Validate file
        $validation = self::validate_file($file);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $file_path = $file['tmp_name'];
        $file_type = $file['type'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        try {
            // Load spreadsheet
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) {
                return new WP_Error('empty_file', __('The uploaded file is empty.', 'excel-status'));
            }
            
            // Find tracking number column
            $tracking_column = self::find_tracking_column($rows[0]);
            
            if ($tracking_column === false) {
                return new WP_Error('no_tracking_column', __('Could not find tracking number column. Please ensure your file has a column with "tracking" in the header.', 'excel-status'));
            }
            
            // Extract tracking numbers
            $tracking_numbers = array();
            for ($i = 1; $i < count($rows); $i++) {
                if (isset($rows[$i][$tracking_column]) && !empty($rows[$i][$tracking_column])) {
                    $tracking_number = trim($rows[$i][$tracking_column]);
                    if ($tracking_number) {
                        $tracking_numbers[] = $tracking_number;
                    }
                }
            }
            
            if (empty($tracking_numbers)) {
                return new WP_Error('no_tracking_numbers', __('No tracking numbers found in the file.', 'excel-status'));
            }
            
            // Find matching orders
            $matched_orders = self::find_orders_by_tracking($tracking_numbers);
            
            if (empty($matched_orders)) {
                return new WP_Error('no_matching_orders', __('No matching orders found for the tracking numbers in the file.', 'excel-status'));
            }
            
            return $matched_orders;
            
        } catch (Exception $e) {
            return new WP_Error('import_error', sprintf(__('Error importing file: %s', 'excel-status'), $e->getMessage()));
        }
    }
    
    /**
     * Validate uploaded file
     * 
     * @param array $file File data
     * @return true|WP_Error
     */
    private static function validate_file($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('File upload error.', 'excel-status'));
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('File size exceeds 5MB limit.', 'excel-status'));
        }
        
        // Check file extension
        $allowed_extensions = array('csv', 'xlsx', 'xls');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_extensions)) {
            return new WP_Error('invalid_file_type', __('Invalid file type. Only CSV, XLS, and XLSX files are allowed.', 'excel-status'));
        }
        
        return true;
    }
    
    /**
     * Find tracking number column in header row
     * 
     * @param array $header_row First row of spreadsheet
     * @return int|false Column index or false if not found
     */
    private static function find_tracking_column($header_row) {
        $tracking_keywords = array('tracking', 'track', 'tracking number', 'tracking_number', 'trackingnumber', 'awb', 'docket');
        
        foreach ($header_row as $index => $cell) {
            $cell_lower = strtolower(trim($cell));
            foreach ($tracking_keywords as $keyword) {
                if (strpos($cell_lower, $keyword) !== false) {
                    return $index;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Find WooCommerce orders by tracking numbers
     * 
     * @param array $tracking_numbers Array of tracking numbers
     * @return array Array of order data
     */
    private static function find_orders_by_tracking($tracking_numbers) {
        global $wpdb;
        
        $tracking_meta_key = get_option('excel_status_tracking_meta_key', '_rj_indiapost_tracking_number');
        $matched_orders = array();
        
        // Query orders with matching tracking numbers
        foreach ($tracking_numbers as $tracking_number) {
            $order_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = %s 
                AND meta_value = %s",
                $tracking_meta_key,
                $tracking_number
            ));
            
            foreach ($order_ids as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $matched_orders[] = array(
                        'order_id' => $order_id,
                        'order_number' => $order->get_order_number(),
                        'customer_name' => $order->get_formatted_billing_full_name(),
                        'customer_email' => $order->get_billing_email(),
                        'status' => $order->get_status(),
                        'tracking_number' => $tracking_number,
                        'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
                        'total' => $order->get_total(),
                        'currency' => $order->get_currency(),
                    );
                }
            }
        }
        
        return $matched_orders;
    }
    
    /**
     * Get sample tracking numbers from file for preview
     * 
     * @param array $file File data
     * @param int $limit Number of samples to return
     * @return array|WP_Error
     */
    public static function get_sample_tracking_numbers($file, $limit = 5) {
        $result = self::import_file($file);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return array_slice($result, 0, $limit);
    }
}

