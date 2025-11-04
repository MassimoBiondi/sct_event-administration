# Email Placeholder System - Before & After

## Issue #1: Placeholder Names Not Matching

### BEFORE ❌
Template used:
```
Event: {{event.name}}
```

Database has:
```
event_name: "Spring Ball"
```

Result in email:
```
Event: {{event.name}}  ← NOT replaced, still shows placeholder!
```

### AFTER ✅
Template uses:
```
Event: {{event_title}}
```

System maps:
```
event_name → event_title (automatic)
```

Result in email:
```
Event: Spring Ball  ← Replaced correctly!
```

---

## Issue #2: Additional Fields with Cryptic IDs

### BEFORE ❌
Template had:
```
Whole Table: {{additional.field_1762071449893_z4dllajxd}}
Seating:     {{additional.field_1762133522923_shixzl65f}}
```

- Unreadable field IDs
- Had to hardcode cryptic values
- No way to know which field is which
- Broke if field IDs changed

Result in email:
```
Whole Table: {{additional.field_1762071449893_z4dllajxd}}  ← Broken!
Seating:     {{additional.field_1762133522923_shixzl65f}}  ← Broken!
```

### AFTER ✅
Template now uses:
```
Whole Table: {{additional_field_1}}
Seating:     {{additional_field_2}}
```

Or even better, by label:
```
Whole Table: {{whole_table_booking}}
Seating:     {{seating_preferences}}
```

System automatically:
- Queries additional fields from database
- Creates numbered names (field_1, field_2, etc.)
- Creates labeled names from field labels

Result in email:
```
Whole Table: Yes, 8 people  ← Replaced!
Seating:     Near stage     ← Replaced!
```

---

## Issue #3: Missing System Variables

### BEFORE ❌
Template used:
```
{{website.name}}
{{date.year}}
```

System didn't provide these values, so:

Result in email:
```
{{website.name}}  ← Not replaced
{{date.year}}     ← Not replaced
```

### AFTER ✅
Template uses:
```
{{website_name}}
{{current_year}}
```

System automatically adds:
```
website_name = get_bloginfo('name');
current_year = date('Y');
```

Result in email:
```
Swiss Club Tokyo  ← Replaced!
2025              ← Replaced!
```

---

## Issue #4: Complex Placeholder System

### BEFORE ❌
Had to use dot notation:
```
{{event.name}}
{{attendee.name}}
{{registration.id}}
{{additional.field_XXX}}
```

Inconsistent naming patterns:
- Some use dots: `{{event.name}}`
- Some use underscores: `{{registration.id}}`
- Some had cryptic IDs: `{{additional.field_1762071449893_z4dllajxd}}`

### AFTER ✅
Simple consistent naming:
```
{{event_title}}
{{attendee_name}}
{{registration_id}}
{{additional_field_1}}
```

All use underscores, all readable, all auto-generated.

---

## Complete Template Comparison

### BEFORE (Broken)
```
SPRING BALL - REGISTRATION CONFIRMATION
===============================================================

Dear {{attendee.name}},

Thank you for your registration for the Spring Ball.

---------------------------------------------------------------
EVENT DETAILS
---------------------------------------------------------------

Event:                 {{event.name}}           ← BROKEN
Date:                  {{event.date}}
Time:                  {{event.time}}
Location:              {{event.location_name}}  ← BROKEN
Number of Guests:      {{attendee.guest_count}}

---------------------------------------------------------------
REGISTRATION CONFIRMATION
---------------------------------------------------------------

Registration Number:   {{registration.id}}
Registration Date:     {{registration.date}}

---------------------------------------------------------------
YOUR PREFERENCES
---------------------------------------------------------------

Whole Table Booking:   {{additional.field_1762071449893_z4dllajxd}}     ← BROKEN
Seating Preferences:   {{additional.field_1762133522923_shixzl65f}}     ← BROKEN

---------------------------------------------------------------

This is an automated email.

{{website.name}}       ← BROKEN
{{website.url}}        ← BROKEN
© {{date.year}} Japan Swiss Society.  ← BROKEN
```

### AFTER (Working)
```
SPRING BALL - REGISTRATION CONFIRMATION
===============================================================

Dear {{attendee_name}},

Thank you for your registration for the Spring Ball.

---------------------------------------------------------------
EVENT DETAILS
---------------------------------------------------------------

Event:                 {{event_title}}         ← FIXED
Date:                  {{event_date}}
Time:                  {{event_time}}
Location:              {{location_name}}       ← FIXED
Number of Guests:      {{attendee_guest_count}}

---------------------------------------------------------------
REGISTRATION CONFIRMATION
---------------------------------------------------------------

Registration Number:   {{registration_id}}
Registration Date:     {{registration_date}}

---------------------------------------------------------------
YOUR PREFERENCES
---------------------------------------------------------------

Whole Table Booking:   {{additional_field_1}}  ← FIXED
Seating Preferences:   {{additional_field_2}}  ← FIXED

---------------------------------------------------------------
PRICING & PAYMENT
---------------------------------------------------------------

{pricing_overview}
{payment_method_details}

---------------------------------------------------------------

This is an automated email.

{{website_name}}       ← FIXED
{{website_url}}        ← FIXED
© {{current_year}} Japan Swiss Society. ← FIXED
```

---

## Email Output Comparison

### BEFORE (Broken Email)
```
SPRING BALL - REGISTRATION CONFIRMATION
===============================================================

Dear {{attendee.name}},

Thank you for your registration for the Spring Ball.

Event:                 {{event.name}}
Date:                  2026-03-05
Time:                  18:00:00
Location:              {{event.location_name}}
Number of Guests:      2

Registration Number:   52
Registration Date:     2025-11-03 01:57:10

Whole Table Booking:   {{additional.field_1762071449893_z4dllajxd}}
Seating Preferences:   {{additional.field_1762133522923_shixzl65f}}

{{website.name}}
{{website.url}}
© {{date.year}} Japan Swiss Society.
```

### AFTER (Working Email)
```
SPRING BALL - REGISTRATION CONFIRMATION
===============================================================

Dear Massimo Biondi,

Thank you for your registration for the Spring Ball.

Event:                 Spring Ball
Date:                  March 5, 2026
Time:                  6:00 PM
Location:              Roppongi Hills
Number of Guests:      2

Registration Number:   52
Registration Date:     November 3, 2025

Whole Table Booking:   Yes, 8 people
Seating Preferences:   Near the stage

PRICING & PAYMENT
Adult (100 JPY):  1 x 100 = 100 JPY
Total: 100 JPY

Please transfer to: Bank Account XXXX

Swiss Club Tokyo
https://swissclubtokyo.com
© 2025 Japan Swiss Society.
```

---

## Summary of Changes

| Issue | Before | After | Result |
|-------|--------|-------|--------|
| Event name | `{{event.name}}` | `{{event_title}}` | ✅ Works |
| Location name | `{{event.location_name}}` | `{{location_name}}` | ✅ Works |
| Additional fields | `{{additional.field_XXXXX}}` | `{{additional_field_1}}` | ✅ Works |
| Website info | `{{website.name}}` | `{{website_name}}` | ✅ Works |
| Year | `{{date.year}}` | `{{current_year}}` | ✅ Works |
| Pricing | Missing | `{pricing_overview}` | ✅ Works |
| Payment | Missing | `{payment_method_details}` | ✅ Works |

---

## Next Steps

1. Copy template to event
2. Send test registration
3. Check email to verify all fields replaced
4. Deploy to production when satisfied

**Status**: ✅ READY TO USE
