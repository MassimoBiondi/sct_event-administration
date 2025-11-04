# Email Placeholder Quick Reference

## Correct Placeholder Names for Spring Ball Template

### IMPORTANT FIX SUMMARY
The previous email template used incorrect placeholder names. Here are the corrections needed:

| ❌ WRONG | ✅ CORRECT | Example Output |
|---------|----------|-----------------|
| `{{event.name}}` | `{{event.title}}` | Spring Ball |
| `{{event.location_name}}` | `{{location.name}}` | Swiss Lakeside Restaurant |
| `{event_name}` | `{{event.title}}` | Spring Ball |

---

## All Available Placeholders

### Attendee Data
```
{{attendee.name}}           → Massimo Biondi
{{attendee.email}}          → massimo@example.com
{{attendee.guest_count}}    → 2
```

### Event Information
```
{{event.title}}             → Spring Ball
{{event.date}}              → March 5, 2026
{{event.time}}              → 6:00 PM
{{event.description}}       → Event description text...
{{location.name}}           → Swiss Club Tokyo
{{location.url}}            → https://maps.google.com/...
```

### Registration
```
{{registration.id}}         → 52
{{registration.date}}       → November 3, 2025
{{registration.manage_link}} → https://example.com/manage?uid=xyz...
```

### Payment (Auto-generated sections)
```
{pricing_overview}          → Auto-generates pricing table (hidden if no pricing)
{payment_method_details}    → Auto-generates payment instructions (hidden if not applicable)
```

### Website
```
{{website.name}}            → Swiss Club Tokyo
{{website.url}}             → https://swissclubtokyo.com
{{date.year}}               → 2025
```

---

## How Sections Are Hidden

The system automatically hides:
- **Pricing section**: Not shown if no pricing options configured or total = 0
- **Payment section**: Not shown if no payment method selected or total = 0

This keeps emails clean and professional - no empty sections!

---

## Template Format

The template supports TWO placeholder formats (both work):

### New Format (Recommended)
```html
Dear {{attendee.name}},
Event: {{event.title}} on {{event.date}}
```

### Old Format (Backward Compatible)
```html
Dear {name},
Event: {event_name} on {event_date}
```

---

## Common Mistakes to Avoid

❌ **INCORRECT:**
```html
<p>Event: {{event.name}}</p>
<p>Location: {{event.location_name}}</p>
<p>Subject: {event_name}</p>
```

✅ **CORRECT:**
```html
<p>Event: {{event.title}}</p>
<p>Location: {{location.name}}</p>
<p>Subject: {event_title}</p>
```

---

## Testing Checklist

Before considering template complete, verify:
- [ ] Attendee name displays correctly
- [ ] Event date/time/location show actual values
- [ ] Pricing table appears if pricing configured
- [ ] Payment instructions show if payment method set
- [ ] All links are clickable
- [ ] No `{{placeholder}}` text remains in email
- [ ] Styling looks good on mobile

---

## Support

If you need to debug:
1. Check WordPress debug.log for placeholder errors
2. Verify event has pricing options configured
3. Ensure registration has all required fields
4. Test with different email clients
5. Clear browser cache and resend test email
