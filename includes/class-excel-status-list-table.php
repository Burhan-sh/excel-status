<?php
/**
 * WP_List_Table for displaying orders
 */

if (!defined('ABSPATH')) {
    exit;
}

class Excel_Status_List_Table extends WP_List_Table {
    
    private $orders_data = array();
    
    /**
     * Constructor
     * 
     * @param array $orders_data Array of order data
     */
    public function __construct($orders_data = array()) {
        parent::__construct(array(
            'singular' => 'order',
            'plural' => 'orders',
            'ajax' => false
        ));
        
        $this->orders_data = $orders_data;
    }
    
    /**
     * Get columns
     * 
     * @return array
     */
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'order_number' => __('Order', 'excel-status'),
            'customer_name' => __('Customer', 'excel-status'),
            'tracking_number' => __('Tracking Number', 'excel-status'),
            'current_status' => __('Current Status', 'excel-status'),
            'new_status' => __('Change Status', 'excel-status'),
            'actions' => __('Actions', 'excel-status'),
        );
    }
    
    /**
     * Get sortable columns
     * 
     * @return array
     */
    protected function get_sortable_columns() {
        return array(
            'order_number' => array('order_number', false),
            'customer_name' => array('customer_name', false),
            'current_status' => array('status', false),
        );
    }
    
    /**
     * Prepare items for display
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Sorting
        usort($this->orders_data, array($this, 'usort_reorder'));
        
        // Pagination - Get user preference or use default
        $per_page = $this->get_items_per_page('orders_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items = count($this->orders_data);
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
        
        $this->items = array_slice($this->orders_data, (($current_page - 1) * $per_page), $per_page);
    }
    
    /**
     * Sort function
     */
    protected function usort_reorder($a, $b) {
        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'order_number';
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
        
        $result = 0;
        if (isset($a[$orderby]) && isset($b[$orderby])) {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }
        
        return ($order === 'asc') ? $result : -$result;
    }
    
    /**
     * Checkbox column
     */
    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="order_ids[]" value="%s" />',
            esc_attr($item['order_id'])
        );
    }
    
    /**
     * Order number column
     */
    protected function column_order_number($item) {
        $order_url = admin_url('post.php?post=' . $item['order_id'] . '&action=edit');
        return sprintf(
            '<a href="%s" target="_blank"><strong>#%s</strong></a><br><small>%s</small>',
            esc_url($order_url),
            esc_html($item['order_number']),
            esc_html($item['date_created'])
        );
    }
    
    /**
     * Customer name column
     */
    protected function column_customer_name($item) {
        return sprintf(
            '<strong>%s</strong><br><small>%s</small>',
            esc_html($item['customer_name']),
            esc_html($item['customer_email'])
        );
    }
    
    /**
     * Tracking number column
     */
    protected function column_tracking_number($item) {
        return '<code>' . esc_html($item['tracking_number']) . '</code>';
    }
    
    /**
     * Current status column
     */
    protected function column_current_status($item) {
        $status = wc_get_order_status_name($item['status']);
        $status_class = 'wc-order-status-' . sanitize_html_class($item['status']);
        
        return sprintf(
            '<mark class="order-status %s"><span>%s</span></mark>',
            esc_attr($status_class),
            esc_html($status)
        );
    }
    
    /**
     * New status column (dropdown)
     */
    protected function column_new_status($item) {
        $statuses = wc_get_order_statuses();
        $output = '<select name="single_status[' . esc_attr($item['order_id']) . ']" class="excel-status-single-select">';
        $output .= '<option value="">' . __('— No Change —', 'excel-status') . '</option>';
        
        foreach ($statuses as $status_key => $status_label) {
            $selected = ($status_key === 'wc-' . $item['status']) ? 'selected' : '';
            $output .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($status_key),
                $selected,
                esc_html($status_label)
            );
        }
        
        $output .= '</select>';
        return $output;
    }
    
    /**
     * Actions column
     */
    protected function column_actions($item) {
        return sprintf(
            '<button type="button" class="button button-small excel-status-update-single" data-order-id="%s">%s</button>',
            esc_attr($item['order_id']),
            __('Update', 'excel-status')
        );
    }
    
    /**
     * Default column
     */
    protected function column_default($item, $column_name) {
        return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
    }
    
    /**
     * Display when no items
     */
    public function no_items() {
        esc_html_e('No orders found. Please upload a CSV/XLSX file with tracking numbers.', 'excel-status');
    }
    
    /**
     * Bulk actions
     */
    protected function get_bulk_actions() {
        $statuses = wc_get_order_statuses();
        $actions = array();
        
        foreach ($statuses as $status_key => $status_label) {
            $actions[$status_key] = sprintf(__('Change to %s', 'excel-status'), $status_label);
        }
        
        return $actions;
    }
}

