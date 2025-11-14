# Excel Order Status Updater for WooCommerce

WordPress plugin jo Excel/CSV files se WooCommerce order statuses ko bulk update karta hai tracking numbers ke basis par.

## Features

✅ **WooCommerce Dependency Check** - Plugin tab hi activate hoga jab WooCommerce active ho
✅ **CSV/XLSX Import** - Dono file formats supported
✅ **Automatic Tracking Column Detection** - Automatically tracking number column find karta hai
✅ **Order Matching** - Tracking numbers se WooCommerce orders ko match karta hai
✅ **WP_List_Table** - Professional table format mein orders dikhata hai
✅ **Single Status Update** - Individual order ka status change karo
✅ **Bulk Status Update** - Multiple orders ka status ek saath change karo
✅ **Custom Tracking Meta Key** - Apni tracking meta key configure kar sakte ho

## Installation

### Important Note:

**Ye plugin automatically aapki theme ki PhpSpreadsheet library use karega** (`Impreza-child/excel_library/vendor/`).

Agar theme mein library nahi hai, to:

```bash
# Theme folder mein library install karein
cd /path/to/themes/Impreza-child
composer require phpoffice/phpspreadsheet
```

**Plugin mein alag se vendor folder install karne ki zarurat NAHI hai!**

### Step 2: Plugin Upload Karein

1. Poora `excel-status` folder ZIP banayein
2. WordPress admin panel mein jayein
3. Plugins → Add New → Upload Plugin
4. ZIP file upload karein aur activate karein

## Usage Guide

### 1. Settings Configure Karein

1. WooCommerce → **Status Updater** par jayein
2. **Settings** section mein:
   - **Tracking Meta Key** field mein apni meta key enter karein
   - Default: `_rj_indiapost_tracking_number`
   - Save Settings click karein

### 2. CSV/XLSX File Prepare Karein

Apni Excel file mein **tracking number** column hona chahiye. Example:

| Order ID | Customer Name | **Tracking Number** | Status |
|----------|---------------|---------------------|--------|
| 1001 | John Doe | TR12345678 | Processing |
| 1002 | Jane Smith | TR87654321 | Completed |

**Important:** Column header mein "tracking" word hona chahiye (case insensitive)

### 3. File Upload Karein

1. **Upload CSV/XLSX File** section mein file select karein
2. **Upload & Import** button click karein
3. Plugin automatically:
   - Tracking column find karega
   - Orders match karega
   - Matched orders table mein dikhayega

### 4. Status Update Karein

#### Single Order Update:
1. **Change Status** column mein dropdown se naya status select karein
2. **Update** button click karein
3. Confirmation ke baad order status update ho jayega

#### Bulk Update:
1. Orders select karne ke liye checkboxes tick karein
2. Table ke upar **Bulk Actions** dropdown se status select karein
3. **Apply** button click karein
4. Multiple orders ek saath update ho jayenge

## File Structure

```
excel-status/
├── excel-status.php                          # Main plugin file
├── includes/
│   ├── class-excel-status-importer.php       # File import & parsing
│   ├── class-excel-status-list-table.php     # WP_List_Table implementation
│   ├── class-excel-status-admin-page.php     # Admin page rendering
│   └── class-excel-status-actions.php        # AJAX actions handler
├── assets/
│   ├── admin.css                             # Admin styles
│   └── admin.js                              # Admin JavaScript
├── vendor/                                    # PhpSpreadsheet library
│   └── autoload.php
├── composer.json                             # Composer dependencies
└── README.md                                 # Documentation
```

## Technical Details

### Tracking Number Meta Key

WooCommerce mein tracking numbers is tarah store hote hain:

```php
$tracking_number = get_post_meta($order_id, '_rj_indiapost_tracking_number', true);
```

Agar aapki tracking meta key alag hai, to Settings mein change kar sakte ho.

### Supported File Formats

- CSV (.csv)
- Excel 2007+ (.xlsx)
- Excel 97-2003 (.xls)

### File Size Limit

Maximum file size: **5MB**

### WooCommerce Order Statuses

Plugin saari default WooCommerce statuses support karta hai:

- Pending Payment
- Processing
- On Hold
- Completed
- Cancelled
- Refunded
- Failed
- Custom Statuses (agar koi plugin add karta hai)

## Troubleshooting

### Plugin Activate Nahi Ho Raha

**Problem:** "This plugin requires WooCommerce" error aa raha hai.

**Solution:** Pehle WooCommerce plugin install aur activate karein.

### Orders Match Nahi Ho Rahe

**Problem:** File upload hone ke baad "No matching orders found" message aa raha hai.

**Solutions:**
1. Settings mein tracking meta key check karein
2. Excel file mein tracking column ka naam check karein (header mein "tracking" word hona chahiye)
3. Tracking numbers exactly match hone chahiye (no extra spaces)

### File Upload Error

**Problem:** File upload ke time error aa raha hai.

**Solutions:**
1. File size 5MB se kam honi chahiye
2. File format CSV ya XLSX hona chahiye
3. PHP memory limit increase karein (wp-config.php mein):
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

### Vendor Folder Empty Hai

**Problem:** "Class 'PhpOffice\PhpSpreadsheet\IOFactory' not found" error.

**Solution:** PhpSpreadsheet library install karein (Installation steps dekhen)

## Support

Koi problem ho to:

1. Plugin folder mein `debug.log` check karein
2. WordPress debug mode enable karein
3. Browser console mein JavaScript errors check karein

## Changelog

### Version 1.0.0 (November 2024)
- Initial release
- CSV/XLSX file import
- Single & bulk status update
- Custom tracking meta key support
- WP_List_Table implementation
- AJAX-based status updates

## Credits

- **PhpSpreadsheet** - Excel file parsing
- **WooCommerce** - E-commerce platform
- **WordPress** - Content management system

## License

GPL v2 or later

---

**Developed by Raju Plastics Team**

