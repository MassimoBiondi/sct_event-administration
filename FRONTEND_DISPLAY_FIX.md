# Multi-Day Events Display Fix - Frontend and Gutenberg

## Problem

Multi-day events without a start time (all-day events with `event_time = NULL`) were not showing up in:
- Event-list shortcode display
- Dashboard widget
- Email query methods

## Root Cause

Multiple queries across the codebase used `CONCAT(event_date, ' ', event_time)` which returns NULL when `event_time` is NULL, causing the comparison to fail and filter out all-day multi-day events.

## Solution

Updated all event query methods to use `COALESCE(event_end_date, event_date) >= CURDATE()` which:
- Handles NULL time values gracefully
- Checks end date for multi-day events
- Works for all event types consistently

## Files Modified

### 1. `includes/class-event-public.php`
**Method:** `event_list_shortcode()`

**Before:**
```php
$query = "SELECT * FROM {$wpdb->prefix}sct_events WHERE CONCAT(event_date, ' ', event_time) >= NOW()";
```

**After:**
```php
$query = "SELECT * FROM {$wpdb->prefix}sct_events WHERE COALESCE(event_end_date, event_date) >= CURDATE()";
```

**Impact:** Fixes `[event_list]` shortcode to display all-day multi-day events

### 2. `includes/class-event-admin.php`
**Method:** `get_events()`

**Before:**
```php
$where_clause = ($type === 'upcoming') 
    ? "WHERE CONCAT(event_date, ' ', event_time) >= NOW()"
    : "WHERE CONCAT(event_date, ' ', event_time) < NOW()";
```

**After:**
```php
$where_clause = ($type === 'upcoming') 
    ? "WHERE COALESCE(event_end_date, event_date) >= CURDATE()"
    : "WHERE COALESCE(event_end_date, event_date) < CURDATE()";
```

**Impact:** Fixes email query methods used for registration notifications

### 3. `sct_event-administraion.php`
**Function:** `sct_event_dashboard_widget_display()`

**Before:**
```sql
WHERE DATE(CONCAT(event_date, ' ', event_time)) >= %s
```

**After:**
```sql
WHERE COALESCE(event_end_date, event_date) >= %s
```

**Impact:** Fixes dashboard widget to show all-day multi-day events

## Gutenberg Block Note

✅ **No changes needed** - The Gutenberg Events List block already uses the correct query:
```sql
AND COALESCE(event_end_date, event_date) >= CURDATE()
```

## Event Types Now Fully Supported

✅ Single-day with time - shortcode ✅, block ✅, widget ✅
✅ Single-day all-day (no time) - shortcode ✅, block ✅, widget ✅
✅ Multi-day with time - shortcode ✅, block ✅, widget ✅
✅ **Multi-day all-day (no time) - shortcode ✅, block ✅, widget ✅ (NOW FIXED)**

## Testing Checklist

- ✅ PHP syntax validated (no errors)
- ✅ [event_list] shortcode displays all-day multi-day events
- ✅ Dashboard widget shows all-day multi-day events
- ✅ Gutenberg block continues to work correctly
- ✅ Email queries include all-day multi-day events
- ✅ Sorting and filtering maintain consistency
- ✅ Backward compatibility maintained

## Version Updated

Plugin version incremented from **2.10.1** → **2.10.2**

Both header version and `EVENT_ADMIN_VERSION` constant updated.

## Impact Summary

- **Scope:** Frontend event display across all interfaces
- **User-Facing:** Events now display consistently everywhere
- **Database:** No schema changes required
- **Backward Compatibility:** 100% - existing events unaffected
- **Performance:** No impact - simpler query logic

---

**Implementation Date:** October 21, 2025
**Status:** ✅ Complete and Validated
