# Spring Ball Email Template - Implementation Guide

## Overview

This document explains the corrected Spring Ball email template and how to properly configure it in the WordPress admin.

## Key Fixes Applied

### 1. **Placeholder Names Corrected**
The original template used incorrect placeholder names. The corrections:

| Old (Incorrect) | New (Correct) | Description |
|-----------------|---------------|-------------|
| `{{event.name}}` | `{{event.title}}` | Event title/name |
| `{{event.location_name}}` | `{{location.name}}` | Location name |

### 2. **Payment Sections Now Display Correctly**
- `{pricing_overview}` - Shows pricing breakdown table (auto-hidden if no pricing)
- `{payment_method_details}` - Shows payment method info (auto-hidden if no payment method)

These are automatically populated by the system when:
- Event has pricing options configured
- Registration has a selected payment method
- Total price is greater than 0

### 3. **All Placeholders Available**

#### Attendee Information
- `{{attendee.name}}` - Registrant's full name
- `{{attendee.email}}` - Registrant's email address
- `{{attendee.guest_count}}` - Number of guests

#### Event Information
- `{{event.title}}` - Event name/title
- `{{event.date}}` - Event date
- `{{event.time}}` - Event time
- `{{event.description}}` - Event description
- `{{location.name}}` - Event location name
- `{{location.url}}` - Location URL/map link

#### Registration Information
- `{{registration.id}}` - Registration/confirmation number
- `{{registration.date}}` - Registration date/time
- `{{registration.manage_link}}` - Link to manage registration

#### Payment Information (Auto-generated)
- `{pricing_overview}` - Pricing table
- `{payment_method_details}` - Payment instructions

#### Website Information
- `{{website.name}}` - Site name
- `{{website.url}}` - Site URL
- `{{date.year}}` - Current year

## How to Apply This Template

1. **Log into WordPress Admin**
2. **Navigate to**: Events → Events List
3. **Click on "Spring Ball" event** to edit it
4. **Scroll to**: "Custom Email Template" field
5. **Click the Source/HTML button** in the editor (if using TinyMCE visual editor)
6. **Copy and paste** the contents of `spring-ball-template.html` file
7. **Click Update** to save

## Template Features

### ✅ Professional Design
- Navy & Gold color scheme (matching Japan Swiss Society branding)
- Georgian serif fonts for elegance
- Responsive layout for mobile devices

### ✅ Smart Section Handling
- Pricing and payment sections automatically hide if no data
- All placeholders use fallback values if data unavailable
- Clean, organized layout

### ✅ Key Information Sections
1. Header with event title
2. Greeting personalized with attendee name
3. Event details (date, time, location, guest count)
4. Confirmation/registration number
5. Registration date
6. Pricing overview (if applicable)
7. Payment method (if applicable)
8. Important information and instructions
9. Manage registration button
10. Contact information
11. Footer with branding

## Important Notes

### Placeholder Format
- Use `{{category.key}}` for standard placeholders
- Use `{variable_name}` for system-generated sections (pricing_overview, payment_method_details)
- Both formats are supported for backward compatibility

### HTML vs Text Email
- This is the HTML version
- The system will also send a plain-text fallback
- Both versions are important for email client compatibility

### Testing
After applying the template, send a test registration to verify:
1. ✅ Placeholders are replaced with actual data
2. ✅ Pricing overview displays correctly
3. ✅ Payment method information shows
4. ✅ Styling renders properly in email clients
5. ✅ Links are clickable

## Troubleshooting

### Placeholders Not Replacing
- Ensure you're using the correct placeholder names
- Check that data is available (e.g., event must have pricing configured)
- Clear browser cache and resend test email

### Sections Not Displaying
- Pricing section only shows if pricing options are configured for event
- Payment section only shows if payment method is selected and total > 0
- This is intentional to keep emails clean

### Styling Issues
- Email client support for CSS varies
- Fallback styles are inline for better compatibility
- Test across multiple email clients (Gmail, Outlook, Apple Mail, etc.)

## Future Enhancements

Potential improvements for the additional fields system:
1. Add conditional sections for custom additional fields
2. Dynamic field handling based on event configuration
3. Custom styling per event
4. A/B testing templates

## Related Files

- Template HTML: `/email-templates/spring-ball-template.html`
- Template PHP: `/email-templates/spring-ball-template.php`
- Additional Fields System: `/includes/class-additional-fields.php`
- Placeholder System: `/includes/class-placeholder-system.php`
- Settings: Admin → Events Settings → Email Templates
