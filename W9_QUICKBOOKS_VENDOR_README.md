# W9 Form & QuickBooks Vendor Integration

## Overview
This system allows contractors (ambassadors) to fill out W-9 forms and automatically creates vendor accounts in QuickBooks marked as 1099 contractors.

## Features
- W-9 form submission for ambassadors
- Automatic QuickBooks vendor creation
- 1099 vendor marking
- Tax identification number storage (SSN/EIN)
- Address and business information capture

## Database Schema

### w9_forms Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `business_name` - Business/Individual name
- `tax_classification` - Tax entity type (individual, c_corp, s_corp, partnership, trust, llc, other)
- `llc_tax_classification` - LLC specific classification
- `other_tax_classification` - Other classification details
- `address` - Street address
- `city` - City
- `state` - State
- `zip_code` - ZIP code
- `ssn_ein` - Social Security Number or Employer Identification Number
- `is_ein` - Boolean flag (true = EIN, false = SSN)
- `signature` - Digital signature (typed name)
- `signature_date` - Date of signature
- `quickbooks_vendor_id` - QuickBooks vendor ID
- `is_1099_vendor` - Boolean flag (default: true)

### users Table (New Fields)
- `quickbooks_vendor_id` - QuickBooks vendor ID
- `w9_submitted` - Boolean flag indicating W-9 submission status

## Setup Instructions

### 1. Run Migration
```bash
php artisan migrate
```

### 2. QuickBooks Configuration
Ensure QuickBooks is connected via the admin panel:
- Navigate to `/admin/quickbooks/auth`
- Complete OAuth authentication
- Verify connection status

### 3. Access W-9 Form
Ambassadors can access the W-9 form at:
```
/ambassador/w9-form
```

## Workflow

### Ambassador W-9 Submission
1. Ambassador navigates to W-9 form page
2. Fills out required information:
   - Business/Individual name
   - Tax classification
   - Address details
   - SSN or EIN
   - Digital signature
3. Submits form
4. System validates data
5. Creates W9Form record
6. Calls QuickBooksService to create vendor
7. Updates user record with vendor ID and submission status

### QuickBooks Vendor Creation
The system automatically:
- Creates vendor in QuickBooks
- Marks vendor as 1099 contractor
- Stores tax identification number
- Links vendor to ambassador account
- Returns vendor ID for tracking

## API Methods

### QuickBooksService::createVendor($vendorData)
Creates a vendor in QuickBooks marked as 1099 contractor.

**Parameters:**
```php
[
    'name' => 'Business Name',
    'email' => 'email@example.com',
    'phone' => '555-1234',
    'address' => '123 Main St',
    'city' => 'City',
    'state' => 'State',
    'zip_code' => '12345',
    'tax_id' => 'XXX-XX-XXXX',
    'is_1099' => true
]
```

**Returns:** QuickBooks Vendor ID or null on failure

## Payment Processing

### Event Payment Flow
1. Ambassador completes event
2. Ambassador submits recap
3. Admin approves recap
4. System creates payout record
5. Payment initiated via Dwolla
6. Invoice created in QuickBooks (linked to vendor)

### 1099 Generation (January)
QuickBooks automatically:
- Tracks all payments to 1099 vendors
- Generates 1099 forms in January
- Sends 1099s to contractors
- Files with IRS

## Security Considerations

### Data Protection
- SSN/EIN stored encrypted in database
- W-9 forms contain sensitive PII
- Access restricted to authenticated ambassadors
- Admin access for viewing submissions

### Compliance
- W-9 certification statement included
- Perjury warning displayed
- Digital signature captured
- Timestamp recorded

## Testing

### Test W-9 Submission
1. Login as ambassador
2. Navigate to `/ambassador/w9-form`
3. Fill out form with test data
4. Submit and verify:
   - Database record created
   - QuickBooks vendor created
   - User flags updated

### Verify QuickBooks Integration
1. Check QuickBooks vendor list
2. Verify 1099 checkbox is marked
3. Confirm vendor details match W-9 submission

## Troubleshooting

### Vendor Creation Fails
- Check QuickBooks connection status
- Verify OAuth token is valid
- Review logs: `storage/logs/laravel.log`
- Ensure required fields are provided

### W-9 Form Not Displaying
- Verify route is registered
- Check middleware authentication
- Confirm view file exists

### 1099 Not Marked
- Check logs for `'vendor_1099' => true` confirmation
- Verify `Vendor1099` property is set before API call
- Ensure tax ID (TaxIdentifier) is provided
- Log into QuickBooks and verify "Track payments for 1099" checkbox
- Check that vendor was created (not updated)

**Code Verification:**
The `Vendor1099` property is set to `true` immediately after vendor object creation:
```php
$vendor->Vendor1099 = true;
```
This ensures QuickBooks tracks all payments for 1099 reporting.

## Future Enhancements
- PDF generation of W-9 forms
- Email notification on submission
- Admin dashboard for W-9 review
- Bulk 1099 export
- Direct deposit setup integration
- W-9 expiration reminders

## Related Files
- Migration: `database/migrations/2026_02_18_000001_create_w9_forms_table.php`
- Model: `app/Models/W9Form.php`
- Controller: `app/Http/Controllers/Ambassador/W9FormController.php`
- Service: `app/Services/QuickBooksService.php` (createVendor method)
- View: `resources/views/ambassador/w9-form.blade.php`
- Routes: `routes/web.php` (ambassador.w9.* routes)
