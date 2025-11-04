# Guest Address & Contact Information Implementation

## Overview

Added comprehensive address and contact information fields to event registrations, allowing guests to provide their full address, telephone number, and company name during event registration. All fields are **configurable per event**.

## Configuration

### Per-Event Settings

When creating or editing an event in the WordPress admin, look for the **"Registration Form Fields"** section with three checkboxes:

- **Collect Phone Number** - Ask guests for their telephone number (default: unchecked)
- **Collect Company Name** - Ask guests for their company or organization name (default: unchecked)
- **Collect Address** - Ask guests for their full address: Street Address, City, Postal Code, Country (default: unchecked)

Each event can have different field requirements.

## Changes Made

### 1. Database Schema Updates

**File:** `sct_event-administraion.php`

**New columns in `sct_event_registrations` table:**
- `company_name` (varchar 255)
- `phone` (varchar 20)
- `address` (varchar 255)
- `city` (varchar 100)
- `postal_code` (varchar 20)
- `country` (varchar 100)

**New columns in `sct_events` table:**
- `collect_phone` (tinyint(1), default 0) - Event-specific setting
- `collect_company` (tinyint(1), default 0) - Event-specific setting
- `collect_address` (tinyint(1), default 0) - Event-specific setting

All fields are optional (DEFAULT NULL/0) to maintain backward compatibility.

### 2. Event Admin Form Updates

**File:** `admin/views/add-event.php`

Added three checkboxes in the event edit form under "Registration Form Fields" section:
- Collect Phone Number
- Collect Company Name
- Collect Address

These checkboxes allow per-event customization of which fields appear on the registration form.

### 3. Frontend Registration Form Updates

**File:** `public/views/event-registration.php`

Updated form to conditionally display fields based on **per-event settings** (event->collect_phone, event->collect_company, event->collect_address):

#### Always Visible
- Name (required)
- Email (required)

#### Conditionally Visible
- **Phone Field** (if `collect_phone` enabled for event)
  - `type="tel"`
  - Optional

- **Company Name Field** (if `collect_company` enabled for event)
  - `type="text"`
  - Optional

- **Address Section** (if `collect_address` enabled for event)
  - **Address** - Textarea with 3 rows for multi-line addresses
  - **City & Postal Code** - Two-column layout on desktop
  - **Country** - Text field

### 4. Registration Processing

**File:** `includes/class-event-public.php`

Unchanged - all fields are optional and handled by sanitization:
```php
$phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
$company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
$address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
$city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
$postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
$country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
```

### 5. Admin Registration Display

**File:** `admin/views/registrations.php`

Registration table conditionally displays columns based on **event settings**:

#### Conditionally Displayed
- **Phone** (if event has `collect_phone` enabled)
- **Company** (if event has `collect_company` enabled)
- **Address** (if event has `collect_address` enabled) - displays full address as: "Street, City, Postal Code, Country"

## Use Cases

### Event A: Corporate Conference
- ✅ Collect Phone Number
- ✅ Collect Company Name
- ✅ Collect Address
- → Full contact information captured

### Event B: Community Workshop
- ✅ Collect Phone Number
- ❌ Collect Company Name
- ❌ Collect Address
- → Only phone number collected

### Event C: Internal Meeting
- ❌ Collect Phone Number
- ❌ Collect Company Name
- ❌ Collect Address
- → Only name and email (original behavior)

## Backward Compatibility

✅ **Fully backward compatible:**
- New database columns default to empty/0
- Existing registrations continue to work
- Old events default to no additional fields (original behavior)
- No breaking changes

## Form Styling

All form fields use UIKit CSS classes:
- `.uk-margin` - Field spacing
- `.uk-form-controls` - Form input styling
- `.uk-grid-small` with `uk-grid` - Responsive column layout
- `uk-width-1-2@m` - 50% width on medium screens

## Address Field Design

Address is a **3-row textarea** to accommodate multi-line addresses including:
- Street address/building number
- Building/apartment complex name
- District/ward name
- Additional location details

Ideal for Japanese and international address formats.

## Export/CSV Considerations

When exporting registrations, the new fields appear as separate columns. Columns only appear if data exists for any registration.

## Testing Checklist

- [ ] Create new event without any collection fields enabled
- [ ] Verify only Name/Email appear on registration form
- [ ] Create second event with all fields enabled
- [ ] Verify all fields appear on registration form
- [ ] Test form submission on both events
- [ ] Verify data saves correctly
- [ ] Check admin interface shows correct columns per event
- [ ] Test CSV export for both events
- [ ] Edit existing event to enable collection fields
- [ ] Verify form updates immediately
- [ ] Test on mobile (fields should respond)

## Future Enhancements

- Add country selector (dropdown)
- Add postal code validation
- Add phone number validation
- Include address in confirmation emails
- Implement address auto-complete
- Add field-level required/optional toggle per event


## Changes Made

### 1. Database Schema Updates

**File:** `sct_event-administraion.php`

Added 7 new columns to the `sct_event_registrations` table:
- `company_name` (varchar 255) - Guest's company or organization name
- `phone` (varchar 20) - Guest's telephone number
- `address` (varchar 255) - Street address
- `city` (varchar 100) - City
- `postal_code` (varchar 20) - Postal/ZIP code
- `country` (varchar 100) - Country

All fields are optional (DEFAULT NULL) to maintain backward compatibility.

### 2. Admin Settings Page

**File:** `admin/views/settings.php`

Added three checkbox options to control registration form field visibility:
- Registration form fields section added to General Settings
- Each field has a descriptive label and help text
- Default state: All unchecked (fields hidden)

### 3. Frontend Registration Form Updates

**File:** `public/views/event-registration.php`

Updated form to conditionally display fields based on admin settings:

#### Always Visible
- Name (required)
- Email (required)

#### Conditionally Visible (based on `registration_field_phone` setting)
- **Phone Field**
  - `type="tel"`
  - Placeholder: "+1 (555) 123-4567"
  - Optional

#### Conditionally Visible (based on `registration_field_company` setting)
- **Company Name Field**
  - `type="text"`
  - Placeholder: "Your company or organization"
  - Optional

#### Conditionally Visible (based on `registration_field_address` setting)
- **Address Field (Textarea with 3 rows)**
  - `type="textarea"` with 3 rows
  - Placeholder: "Street address, Building, Apartment, etc."
  - Better for Japanese and multi-line addresses
  - Optional

- **City & Postal Code Fields**
  - Split into two-column layout on desktop
  - `city` and `postal_code` fields
  - Optional

- **Country Field**
  - `type="text"`
  - Placeholder: "Country"
  - Optional

### 4. Registration Processing Updates

**File:** `includes/class-event-public.php` - `process_registration()` method

Updated registration handler to:
- Accept and sanitize all new fields from the form
- Include address data in the registration_data array
- Updated INSERT statement to include all 16 data fields with proper placeholder types

New sanitization:
```php
$phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
$company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
$address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
$city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
$postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
$country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
```

### 5. Admin Registration Display

**File:** `admin/views/registrations.php`

Updated the registrations management table to conditionally display new fields:

#### Conditionally Displayed Columns
- **Phone** (if `registration_field_phone` enabled) - collapsed on mobile
- **Company** (if `registration_field_company` enabled) - collapsed on mobile
- **Address** (if `registration_field_address` enabled) - collapsed on mobile, displays full address as: "Street, City, Postal Code, Country"

Empty values display as "—" for clarity.

## Backward Compatibility

✅ **Fully backward compatible:**
- New database columns are optional (DEFAULT NULL)
- Existing registrations continue to work
- Form fields are hidden by default
- Admin interface gracefully displays empty values as "—"
- No breaking changes to existing functionality

## Form Styling

All form fields use UIKit CSS classes:
- `.uk-margin` - Field spacing
- `.uk-form-controls` - Form input styling
- `.uk-grid-small` with `uk-grid` - Responsive column layout for City/Postal Code
- `uk-width-1-2@m` - 50% width on medium screens and up

## Address Field Design

The Address field is a **3-row textarea** to accommodate Japanese addresses which often include:
- Street address/building number
- Building/apartment complex name
- District/ward name
- Additional location details

This format is more flexible than a single-line input field.

## Export/CSV Considerations

When exporting registrations to CSV, the new fields will be included as separate columns **only when enabled**:
- Column: Phone (if enabled)
- Column: Company Name (if enabled)
- Column: Address (if enabled)
- Column: City (if enabled)
- Column: Postal Code (if enabled)
- Column: Country (if enabled)

## Testing Checklist

- [ ] Navigate to Event Administration Settings
- [ ] Verify checkboxes for Phone, Company, Address fields appear
- [ ] Check default state (all unchecked)
- [ ] Enable Phone field checkbox and save
- [ ] Verify phone field appears on registration form
- [ ] Disable Phone field and verify it disappears
- [ ] Enable Company field and test
- [ ] Enable Address fields and test
- [ ] Verify Address field is a textarea with 3 rows
- [ ] Test form submission with fields enabled
- [ ] Verify data saves to database
- [ ] Test admin interface displays fields conditionally
- [ ] Test CSV export includes only enabled fields
- [ ] Test on mobile (fields should collapse appropriately)
- [ ] Test with no additional fields enabled (original behavior)
- [ ] Test existing registrations still appear regardless of settings

## Future Enhancements

Potential improvements for future versions:
- Add country selector (dropdown) instead of text field
- Add postal code validation by country
- Add phone number format validation
- Include address fields in confirmation email templates
- Add address fields to guest_details for group registrations
- Implement auto-complete for address fields
- Add optional/required field toggle for each field

