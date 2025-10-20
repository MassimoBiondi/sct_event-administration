# Events List Block - Register Button & Thumbnails - Quick Guide

## What's New

### ✨ Event Thumbnails
- Shows eye-catching event image at top of card
- **Automatic Selection:**
  1. Explicit thumbnail (uploaded in event admin) → Use it
  2. No explicit thumbnail? → Extract first image from description
  3. No images found? → Display clean card without image

### 🔘 Register Button
- **Internal Events**: Blue button linking to registration page
  ```
  /events/?id=123
  ```
- **External Events**: Green button with external link icon ↗
  ```
  https://external-site.com/register
  ```

---

## Card Layout

### Before
```
┌─────────────────────┐
│  Event Title        │
│  Date & Time        │
│  Location           │
│  Description...     │
└─────────────────────┘
```

### After (With Improvements)
```
┌─────────────────────┐
│   [THUMBNAIL]       │ ← NEW
├─────────────────────┤
│  Event Title        │
│  Date & Time        │
│  Location           │
│  Capacity Warning   │
│  Description...     │
├─────────────────────┤
│   [REGISTER BTN]    │ ← NEW
└─────────────────────┘
```

---

## Visual Examples

### Example 1: Internal Event with Thumbnail
```
┌──────────────────────────────┐
│  [Summer Conference Image]   │
├──────────────────────────────┤
│  Summer Conference 2025      │
│  Aug 15, 2025 at 2:00 PM     │
│  Downtown Convention Center  │
│  Join us for amazing talks   │
├──────────────────────────────┤
│          REGISTER            │  ← Blue
└──────────────────────────────┘
```

### Example 2: External Event
```
┌──────────────────────────────┐
│  [Extracted from descr.]     │
├──────────────────────────────┤
│  Partner Workshop            │
│  Aug 20, 2025 at 10:00 AM    │
│  Partner HQ Building         │
│  ⚠ Only 2 spots left         │
│  Limited workshop capacity   │
├──────────────────────────────┤
│  REGISTER (External) ↗       │  ← Green
└──────────────────────────────┘
```

### Example 3: All-Day Multi-Day Event
```
┌──────────────────────────────┐
│  [Default gray background]   │  ← No image
├──────────────────────────────┤
│  Annual Retreat              │
│  August 25-27, 2025          │
│  Mountain Resort             │
│  Join us for three days...   │
├──────────────────────────────┤
│          REGISTER            │
└──────────────────────────────┘
```

### Example 4: Fully Booked
```
┌──────────────────────────────┐
│   [Thumbnail Image]          │
├──────────────────────────────┤
│  Premium Dinner              │
│  Sep 5, 2025 at 7:00 PM      │
│  Elegant Restaurant          │
│  ❌ Fully Booked             │
│  Seats completely full       │
├──────────────────────────────┤
│          REGISTER            │
└──────────────────────────────┘
```

---

## How Thumbnails Work

### Priority System
```
1. Explicit Thumbnail (in admin)
   ↓ (if empty)
2. First <img> tag in description
   ↓ (if none found)
3. WordPress shortcode [wp-image-ID]
   ↓ (if none found)
4. No image (shows gray background)
```

### Thumbnail Extraction Examples

**HTML Image in Description:**
```html
<p>Join us for this amazing event!</p>
<img src="https://example.com/event.jpg" alt="Event" />
<p>More details...</p>
```
✓ Extracts: `https://example.com/event.jpg`

**WordPress Shortcode:**
```
[wp-image-123]
```
✓ Extracts attachment image for ID 123

**No Image:**
```
Just text description...
```
✓ Shows clean card without image

---

## Button Behavior

### Internal Registration Button
- **Color**: Blue (`#0073aa`)
- **Hover**: Darker blue (`#005a87`)
- **Links to**: Event registration page + event ID
- **Text**: "Register"
- **Opens**: Same window

Example URL:
```
https://mysite.com/register/?id=42
```

### External Registration Button
- **Color**: Green (`#28a745`)
- **Hover**: Darker green (`#218838`)
- **Links to**: External URL from event settings
- **Text**: Custom per event (or "Register")
- **Icon**: External link indicator ↗
- **Opens**: New tab

Example:
```
REGISTER (External) ↗
```

---

## Settings & Configuration

### In Event Admin
1. **Explicit Thumbnail**
   - Upload via "Event Thumbnail" button
   - Stored in database
   - Takes priority over description images

2. **External Registration**
   - Enable "External Registration" checkbox
   - Set external URL
   - (Optional) Set custom button text

### In Block Settings
- No new settings needed!
- Button appears automatically
- Thumbnail auto-detected

---

## CSS Styling

### Thumbnail Size
- **Height**: 200px (fixed)
- **Width**: 100% (responsive)
- **Aspect Ratio**: Maintains original (object-fit: cover)
- **Background**: Light gray if no image

### Button Styling
- **Width**: Full card width
- **Padding**: 12px vertical, 16px horizontal
- **Border Radius**: 5px (slightly rounded)
- **Font Weight**: Bold (600)
- **Hover**: Color transition + text remains white

### Card Spacing
- **Thumbnail**: No padding (full bleed)
- **Content**: 20px padding
- **Footer**: 15px padding, light background

---

## Responsive Behavior

### Desktop (>1024px)
- 1-4 column layout
- Full image height (200px)
- Smooth hover animation
- Proper spacing maintained

### Tablet (768-1024px)
- 2 columns (3/4 col → 2 col)
- Image displays normally
- Button fully accessible
- Touch-friendly sizing

### Mobile (<768px)
- Single column
- 200px image height
- Full-width button
- Optimized spacing

---

## Color Scheme

| Element | Normal | Hover | Background |
|---------|--------|-------|------------|
| Internal Button | `#0073aa` (blue) | `#005a87` (darker blue) | white |
| External Button | `#28a745` (green) | `#218838` (darker green) | white |
| Thumbnail BG | - | - | `#f5f5f5` (light gray) |
| Footer BG | - | - | `#fafafa` (very light gray) |

---

## Accessibility

✓ **Image Alt Text**: Event name used
✓ **Button Text**: Clear ("Register", "Register (External)")
✓ **Icon Labels**: External link clearly marked with ↗
✓ **Touch Targets**: Minimum 44px height
✓ **Color Contrast**: WCAG AA compliant
✓ **Semantic HTML**: Proper link elements

---

## Performance Impact

- ✓ No JavaScript on frontend (PHP rendered)
- ✓ Single image per card
- ✓ Images lazy-loadable by browser
- ✓ Efficient CSS grid layout
- ✓ Smooth hardware-accelerated animations

---

## Browser Support

- ✓ Modern browsers (Chrome, Firefox, Safari, Edge)
- ✓ Mobile browsers (iOS Safari, Chrome Mobile)
- ✓ Responsive design works everywhere
- ✓ Object-fit supported in >99% of browsers

---

## Quick Tips

1. **Best Thumbnail Size**: 16:9 aspect ratio (1600x900px)
   - Scales beautifully in 200px height

2. **External Registration**: Set both URL and button text
   - Makes it clear where user is going

3. **Multi-column Layout**: Use 3-4 columns on desktop
   - Better visual balance with thumbnails

4. **Description Images**: Keep first image relevant
   - Users will see it in the block

5. **Mobile Testing**: Always preview on phones
   - Thumbnails are important for mobile UX

---

## Troubleshooting

### Thumbnail Not Showing
→ Check if event has thumbnail uploaded OR description contains image

### Button Text Wrong
→ For external registrations, edit event and set "External Registration Text"

### Button Not Linking
→ Verify registration page is set in plugin settings

### Image Broken
→ Check external image URL is still valid

### Card Layout Broken
→ Clear browser cache (CSS updated)
