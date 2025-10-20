# Events List Block - Documentation

## Overview

The **Events List** block is a new Gutenberg block that displays events on your website. It's similar to the "Latest Posts" block but specifically designed for event management.

## Features

✅ **Flexible Display Options:**
- Choose number of events to display (1-50)
- Sort by date (newest/oldest first) or name (A-Z or Z-A)
- Display events in 1-4 columns
- Responsive design (automatically adjusts on mobile)

✅ **Customizable Content:**
- Show/hide event date and time
- Show/hide event location (with map link)
- Show/hide event capacity
- Show/hide event description

✅ **Smart Filtering:**
- Only shows published events
- Respects publish/unpublish dates
- Automatically hides past and unpublished events

✅ **Professional Styling:**
- Clean, modern card design
- Hover effects and animations
- Mobile-responsive grid layout
- Fully customizable via CSS

## How to Use

### 1. Insert the Block

1. Create or edit a page/post in WordPress
2. Click the **+** button to add a new block
3. Search for **"Events List"**
4. Click on the block to insert it

### 2. Configure Block Settings

In the right sidebar Inspector panel, you'll see:

**Events List Settings:**
- **Number of Events** - How many events to display (1-50)
- **Columns** - Layout in 1-4 columns
- **Sort By** - Order events by:
  - Event Date (Newest First)
  - Event Date (Oldest First)
  - Event Name (A-Z)
  - Event Name (Z-A)

**Display Options:**
- **Show Event Date & Time** - Toggle date/time display
- **Show Location** - Toggle location display
- **Show Capacity** - Toggle capacity display
- **Show Description** - Toggle event description

### 3. Save and Publish

Click "Save" or "Update" to save your page with the Events List block.

## Block Features

### Event Information Displayed

Each event card shows:

**Title** - Event name (always shown)

**Date & Time** (if enabled)
- Formatted according to your WordPress date/time settings

**Location** (if enabled)
- Location name
- Optional "View on Map" link if location URL is provided

**Capacity** (if enabled)
- Number of guest spots available

**Description** (if enabled)
- First 20 words of the event description
- Trimmed for brevity in the block

### Responsive Design

The block automatically adjusts to different screen sizes:
- **Desktop:** Full column count (1-4 as configured)
- **Tablet:** 2-column layout (for 3+ columns)
- **Mobile:** Single column (always)

## Block Filtering

Events are automatically filtered based on:

**Publish Date**
- Events only show if `publish_date` is empty OR `publish_date` has passed

**Unpublish Date**
- Events hide if `unpublish_date` is set and has been reached

**Status**
- Uses WordPress's built-in post publishing system

## Example Configurations

### Config 1: Upcoming Events Highlight
- **Number:** 5
- **Columns:** 1
- **Sort By:** Event Date (Oldest First)
- **Show:** Date, Location, Capacity, Description
- **Result:** Next 5 events in a detailed list

### Config 2: Event Grid
- **Number:** 12
- **Columns:** 3
- **Sort By:** Event Date (Newest First)
- **Show:** Date, Location, Description
- **Hide:** Capacity
- **Result:** Grid of 12 upcoming events

### Config 3: Simple List
- **Number:** 10
- **Columns:** 1
- **Sort By:** Event Name (A-Z)
- **Show:** Date, Location
- **Hide:** Capacity, Description
- **Result:** Simple alphabetical list of events

## CSS Customization

The block uses these CSS classes for styling:

```css
.sct-events-list-block          /* Main wrapper */
.sct-events-grid                /* Grid container */
.sct-events-columns-[1-4]       /* Column count (1-4) */
.sct-event-item                 /* Individual event card */
.sct-event-title                /* Event title */
.sct-event-meta                 /* Date/time info */
.sct-event-date                 /* Date specifically */
.sct-event-time                 /* Time specifically */
.sct-event-location             /* Location info */
.sct-event-capacity             /* Capacity info */
.sct-event-description          /* Description text */
```

To customize, add CSS to your theme's `style.css`:

```css
.sct-event-item {
    background-color: #f5f5f5;
    border-radius: 12px;
    padding: 25px;
}

.sct-event-title {
    color: #1a73e8;
    font-size: 22px;
}
```

## Events Included

The block displays events from the `sct_events` table with the following fields:

- `event_name` - Event title
- `event_date` - Event date
- `event_time` - Event time
- `location_name` - Location/venue name
- `location_link` - Map or website URL
- `guest_capacity` - Total guest spots
- `description` - Event details
- `publish_date` - When to show the event
- `unpublish_date` - When to hide the event

## Frequently Asked Questions

**Q: Can I use multiple Events List blocks on the same page?**
A: Yes! Each block can have different settings and configurations.

**Q: Why aren't all events showing?**
A: Check that events have:
- `publish_date` is empty or in the past
- `unpublish_date` is empty or in the future
- Events exist in your database

**Q: Can I add a custom class to the block?**
A: Yes, click the block and use the Advanced panel to add custom CSS classes.

**Q: How do I style the events cards?**
A: Use the CSS classes listed above or add custom CSS in your theme.

**Q: Can I link event titles to the registration page?**
A: The current version doesn't include this, but you can modify the PHP file `class-event-list-block.php` to add links.

## Technical Details

**Block Slug:** `sct-events/list`

**Block ID:** `sct-events-list-block`

**Files:**
- PHP: `includes/class-event-list-block.php`
- JavaScript: `admin/js/event-list-block.js`
- CSS (Editor): `admin/css/event-list-block-editor.css`
- CSS (Frontend): `public/css/event-list-block.css`

**Dependencies:**
- WordPress 5.0+ (Gutenberg)
- SCT Event Administration plugin
- Database table: `wp_sct_events`

## Support

For issues or feature requests, please contact the plugin support team.
