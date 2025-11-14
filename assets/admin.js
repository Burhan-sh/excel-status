/**
 * Excel Status Updater - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Single Order Status Update
         */
        $(document).on('click', '.excel-status-update-single', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var orderId = $button.data('order-id');
            var $select = $('select[name="single_status[' + orderId + ']"]');
            var newStatus = $select.val();
            
            // Validate
            if (!newStatus) {
                alert(excelStatusData.selectStatus);
                return;
            }
            
            // Confirm
            if (!confirm('Are you sure you want to update the status of order #' + orderId + '?')) {
                return;
            }
            
            // Disable button
            $button.prop('disabled', true).text('Updating...');
            
            // Send AJAX request
            $.ajax({
                url: excelStatusData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'excel_status_single_update',
                    nonce: excelStatusData.nonce,
                    order_id: orderId,
                    new_status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showMessage(response.data.message, 'success');
                        
                        // Update current status column
                        var $statusCell = $button.closest('tr').find('td.column-current_status');
                        $statusCell.html('<mark class="order-status"><span>' + response.data.new_status + '</span></mark>');
                        
                        // Reset button
                        $button.prop('disabled', false).text('Update');
                    } else {
                        showMessage(response.data.message, 'error');
                        $button.prop('disabled', false).text('Update');
                    }
                },
                error: function() {
                    showMessage('An error occurred. Please try again.', 'error');
                    $button.prop('disabled', false).text('Update');
                }
            });
        });
        
        /**
         * Bulk Order Status Update
         */
        $(document).on('click', '#doaction, #doaction2', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var actionName = $button.attr('id') === 'doaction' ? 'action' : 'action2';
            var bulkStatus = $('select[name="' + actionName + '"]').val();
            var $checkboxes = $('input[name="order_ids[]"]:checked');
            
            // Validate
            if ($checkboxes.length === 0) {
                alert(excelStatusData.selectOrders);
                return;
            }
            
            if (!bulkStatus || bulkStatus === '-1') {
                alert(excelStatusData.selectStatus);
                return;
            }
            
            // Confirm
            if (!confirm(excelStatusData.confirmBulk)) {
                return;
            }
            
            // Get order IDs
            var orderIds = [];
            $checkboxes.each(function() {
                orderIds.push($(this).val());
            });
            
            // Disable button
            $button.prop('disabled', true).text('Updating...');
            
            // Send AJAX request
            $.ajax({
                url: excelStatusData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'excel_status_bulk_update',
                    nonce: excelStatusData.nonce,
                    order_ids: orderIds,
                    bulk_status: bulkStatus
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage(response.data.message, 'error');
                        $button.prop('disabled', false).text('Apply');
                    }
                },
                error: function() {
                    showMessage('An error occurred. Please try again.', 'error');
                    $button.prop('disabled', false).text('Apply');
                }
            });
        });
        
        /**
         * Select All Checkboxes
         */
        $(document).on('change', 'thead .check-column input[type="checkbox"], tfoot .check-column input[type="checkbox"]', function() {
            var $this = $(this);
            var checked = $this.prop('checked');
            $this.closest('table').find('tbody .check-column input[type="checkbox"]').prop('checked', checked);
        });
        
        /**
         * Show Message
         */
        function showMessage(message, type) {
            // Remove existing messages
            $('.excel-status-message').remove();
            
            // Create message element
            var $message = $('<div class="excel-status-message ' + type + '"></div>').text(message);
            
            // Insert after heading
            $('.excel-status-wrap h1').after($message);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);
        }
        
        /**
         * File Upload Validation
         */
        $('input[type="file"][name="excel_status_file"]').on('change', function() {
            var file = this.files[0];
            var allowedExtensions = ['csv', 'xlsx', 'xls'];
            var fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (allowedExtensions.indexOf(fileExtension) === -1) {
                alert('Please select a valid CSV or XLSX file.');
                $(this).val('');
                return false;
            }
            
            // Check file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB. Please select a smaller file.');
                $(this).val('');
                return false;
            }
        });
        
        /**
         * Highlight changed status
         */
        $(document).on('change', '.excel-status-single-select', function() {
            var $select = $(this);
            if ($select.val()) {
                $select.css('border-color', '#007cba');
            } else {
                $select.css('border-color', '');
            }
        });
        
    });
    
})(jQuery);

