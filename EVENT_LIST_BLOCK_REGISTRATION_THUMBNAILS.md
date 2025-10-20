# Events List Block - Register Button & Thumbnails

## New Features Added

### 1. **Event Thumbnail Display**

#### Automatic Thumbnail Selection
The block intelligently chooses thumbnails with fallback logic:

1. **First Priority**: Explicit thumbnail set in event admin
   - Set via "Event Thumbnail" upload in event creation
   - Stored in `thumbnail_url` database field

2. **Second Priority**: First image from event description
   - Extracts `<img>` tags from description HTML
   - Supports WordPress image shortcodes `[wp-image-ID]`
   - Falls back to attachment image if shortcode found

3. **No Image**: Displays clean card without thumbnail
   - Event still displays normally
   - No broken image icons

#### Thumbnail Display
- **Size**: 200px height (responsive width)
- **Aspect Ratio**: Maintains original via `object-fit: cover`
- **Position**: Top of card for visual impact
- **Hover**: Smooth lift effect on entire card

### 2. **Register Button**

#### Internal Registration
- Links to event registration page
- Passes event ID as URL parameter: `?id=123`
- Button label: "Register"
- Color: Blue (`#0073aa` → `#005a87` on hover)

#### External Registration
- When external registration enabled for event
- Links to external registration URL
- Button text: Customizable per event
- Shows external link icon (↗)
- Color: Green (`#28a745` → `#218838` on hover)
- Opens in new tab

#### Button Features
- Full width within card
- Clear, readable text
- Smooth hover color transition
- Proper spacing and padding
- Mobile-friendly touch targets

### 3. **Card Layout Redesign**

#### Visual Hierarchy
```
┌─────────────────────────┐
│     [THUMBNAIL]         │  ← New: Eye-catching image
├─────────────────────────┤
│  Event Title            │
│  Date & Time            │
│  Location               │
│  Capacity Notice        │
│  Description            │  ← Content flex-grows
│                         │
├─────────────────────────┤
│    [REGISTER BUTTON]    │  ← New: Sticky at bottom
└─────────────────────────┘
```

#### Structure
1. **Thumbnail** (optional)
   - Full width, fixed height
   - Image-only container
   
2. **Content** (flexible)
   - Title, date, location, notices, description
   - Grows to fill available space
   - Padding on sides
   
3. **Footer** (fixed)
   - Register button
   - Separate background color
   - Stands out visually

### 4. **CSS Classes**

#### New Classes
- `.sct-event-thumbnail` - Image container
- `.sct-event-content` - Main content wrapper
- `.sct-event-footer` - Button footer area
- `.sct-register-button` - Base button style
- `.sct-register-internal` - Internal registration button
- `.sct-register-external` - External registration button
- `.sct-external-icon` - External link indicator

## PHP Implementation

### Helper Method: `get_event_thumbnail()`

```php
private function get_event_thumbnail($event)
```

**Logic:**
1. Check for explicit `thumbnail_url` field
2. If not found, extract from description HTML
3. Search for `<img src="...">` tags
4. Search for WordPress shortcodes `[wp-image-ID]`
5. Return URL or null if nothing found

**Escaping:**
- All URLs passed through `esc_url()`
- Safe for use in `src` attributes
- Prevents XSS vulnerabilities

### Updated: `render_event_item()`

**Changes:**
- Gets thumbnail via helper method
- Wraps content in `.sct-event-content` div
- Adds footer section with register button
- Determines registration type (internal/external)
- Builds proper registration URLs

**Registration Logic:**
```php
if (external_registration enabled) {
    use external_registration_url
    show custom text or "Register"
    open in new tab
} else {
    use internal registration page
    append event ID as query param
}
```

## Database Queries

### Thumbnail Extraction
- Uses PHP regex to find image tags in HTML
- No additional database queries
- Efficient single-pass search

### Registration URL Building
- Uses `get_option('event_admin_settings')` for page ID
- Falls back to current page if not set
- Uses `add_query_arg()` for URL building

## CSS Styling

### Thumbnail Container
```css
.sct-event-thumbnail {
    width: 100%;           /* Full card width */
    height: 200px;         /* Fixed height */
    overflow: hidden;      /* Prevent overflow */
    background-color: #f5f5f5; /* Light gray background */
}

.sct-event-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;     /* Maintains aspect ratio */
    display: block;
}
```

### Content Area
```css
.sct-event-content {
    padding: 20px;         /* Side padding */
    flex: 1;               /* Takes remaining space */
    display: flex;
    flex-direction: column;
}
```

### Footer & Button
```css
.sct-event-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    background-color: #fafafa; /* Subtle background */
}

.sct-register-button {
    width: 100%;           /* Full width */
    padding: 12px 16px;    /* Comfortable touch target */
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s;  /* Smooth hover */
}
```

## Button Colors & States

### Internal Registration
| State | Background | Text |
|---|---|---|
| Normal | `#0073aa` | White |
| Hover | `#005a87` | White |

### External Registration
| State | Background | Text |
|---|---|---|
| Normal | `#28a745` | White |
| Hover | `#218838` | White |

## Responsive Design

### Desktop (>1024px)
- Thumbnail displays at full height (200px)
- Card grows with grid layout
- Button fully visible
- Hover effects smooth

### Tablet (768-1024px)
- Thumbnail scales with card
- Content readable
- Button accessible
- Multi-column layout responsive

### Mobile (<768px)
- Thumbnail 200px but narrower
- Content stacks vertically
- Full-width button
- Touch-friendly sizing

## Accessibility Features

- ✓ Semantic HTML with proper structure
- ✓ Descriptive link text ("Register", not "Click here")
- ✓ External links properly labeled with icon
- ✓ Images have alt text (event name)
- ✓ Sufficient color contrast for buttons
- ✓ Sufficient button size for touch (44px minimum)
- ✓ Proper focus states (browser default)

## Browser Support

- ✓ Chrome/Edge (latest) - Full support
- ✓ Firefox (latest) - Full support
- ✓ Safari (latest) - Full support
- ✓ Mobile browsers - Full support
- ✓ IE 11 - Degraded (uses CSS grid)

## Performance

### Image Optimization
- No image resizing on server (browser handles via CSS)
- Images loaded from WordPress media or external source
- Single HTTP request per image
- Cached by browser

### CSS Grid
- Hardware accelerated layout
- Minimal JavaScript overhead
- Efficient hover animations

### DOM Structure
- Minimal HTML elements
- Efficient selectors
- Fast CSS painting

## Example Use Cases

### Use Case 1: Simple Internal Events
```
Event: Summer Conference
[Thumbnail from media library]
Date: Aug 15, 2025 at 2:00 PM
Location: Downtown Hall
Description preview...
[REGISTER Button]
```

### Use Case 2: External Registration
```
Event: Partner Workshop
[Thumbnail extracted from description]
Date: Aug 20, 2025
Location: Partner HQ
[REGISTER (External) ↗ Button]
```

### Use Case 3: Multi-Day Event
```
Event: Annual Retreat
[Auto-generated from first image]
Date: Aug 25-27, 2025
Location: Mountain Resort
Low capacity warning shown
[REGISTER Button]
```

## Testing Checklist

- [ ] Explicit thumbnail displays correctly
- [ ] Missing thumbnail falls back to description image
- [ ] No image shows clean card without errors
- [ ] Internal registration button links correctly
- [ ] External registration button links correctly
- [ ] Register button fully visible on mobile
- [ ] Button colors correct on hover
- [ ] Thumbnail aspect ratio maintained
- [ ] Card layout stacks correctly on mobile
- [ ] All links open in correct target (self/blank)
- [ ] Button text readable and accessible
- [ ] External icon displays properly

## Troubleshooting

### Thumbnail Not Showing
1. Check event has explicit thumbnail OR
2. Check description contains `<img>` tag OR
3. Check description contains `[wp-image-ID]` shortcode

### Button Not Linking
1. Verify registration page set in settings OR
2. Check external URL is valid (if external)
3. Check event ID properly appended to URL

### Button Text Wrong
1. Check external_registration_text is set (external only)
2. Verify translations are loaded

## Future Enhancements (Optional)

- [ ] Multiple thumbnail options (carousel)
- [ ] Lazy loading for images
- [ ] Image filters/overlays
- [ ] Button loading state
- [ ] Quick view modal
- [ ] Share/favorite buttons
- [ ] Event countdown timer
