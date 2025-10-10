# Placeholder Format Recommendation: `{{double.braces}}`

## 🎯 **RECOMMENDATION: Use `{{category.property}}` Format**

After analyzing both formats, **double braces with dot notation** is the superior choice for your SCT plugins.

## **Current State Analysis**

### ❌ **Event Plugin (OLD)**: Single Braces `{placeholder}`
```
{name} → Attendee name
{event_name} → Event title  
{total_price} → Payment total
```

### ✅ **Mailing Plugin (NEW)**: Double Braces `{{placeholder}}`
```
{{recipient.name}} → Recipient name
{{campaign.name}} → Campaign title
{{payment.total}} → Payment total
```

## **Why `{{double.braces}}` is Better**

### **1. Industry Standard Compatibility**
| Template Engine | Syntax | Usage |
|----------------|--------|-------|
| **Handlebars.js** | `{{variable}}` | 20M+ weekly downloads |
| **Mustache** | `{{variable}}` | Cross-language standard |
| **Twig (Symfony)** | `{{variable}}` | PHP's modern template engine |
| **Django** | `{{variable}}` | Python's web framework |
| **Angular** | `{{variable}}` | Frontend framework |

### **2. Collision Avoidance**
```php
// PROBLEMATIC with single braces:
$styles = "{color: red; margin: {$spacing}px}";  // CSS conflicts
$array = array('key' => '{value}');               // PHP conflicts

// SAFE with double braces:
$template = "Hello {{user.name}}";                // Clear distinction
```

### **3. Visual Clarity**
```
Single:  {user_name} vs {css_rule}     ← Ambiguous
Double:  {{user.name}} vs {css: rule}  ← Crystal clear
```

### **4. Hierarchical Structure**
```
// Better organization with dot notation:
{{event.title}}           vs  {event_name}
{{event.location.name}}   vs  {location_name}
{{attendee.contact.email}} vs {email}

// Categories make relationships clear:
{{payment.total}}    ← Obviously payment-related
{{payment.status}}   ← Part of payment category
{{event.date}}       ← Part of event category
```

### **5. Future Extensibility**
```handlebars
<!-- Conditional logic (future) -->
{{#if payment.required}}
  Total: {{payment.total}}
{{/if}}

<!-- Formatting filters (future) -->
{{payment.total|currency}}
{{event.date|dateFormat:'Y-m-d'}}

<!-- Nested properties (future) -->
{{event.location.address.street}}
```

## **Implementation Status**

### ✅ **Already Updated**
1. **Base placeholder system** supports both formats
2. **Backward compatibility** maintained - old `{placeholders}` still work
3. **Event admin UI** updated to show new format
4. **All three plugins** have standardized processing

### 🔄 **Migration Strategy**

#### **Phase 1: Coexistence** (Current)
- Both formats work simultaneously
- New templates use `{{category.property}}`  
- Old templates continue with `{placeholder}`

#### **Phase 2: Encouraged Migration** (Recommended)
- Update admin UI to promote new format
- Show examples with `{{category.property}}`
- Provide migration assistance

#### **Phase 3: Full Standardization** (Future)
- Gradually deprecate old format
- Focus on `{{category.property}}` exclusively

## **Practical Benefits for Your Plugins**

### **Before (Single Braces)**
```
Fragmented:  {name}, {email}, {event_name}, {total_price}
Unclear:     What category does {name} belong to?
Limited:     Hard to extend with related properties
```

### **After (Double Braces)**
```
Organized:   {{attendee.name}}, {{attendee.email}}
Clear:       {{event.title}}, {{event.date}}
Structured:  {{payment.total}}, {{payment.status}}
Extensible:  {{location.name}}, {{location.address}}, {{location.link}}
```

## **Template Examples**

### **Event Confirmation Email**
```handlebars
Dear {{attendee.name}},

Thank you for registering for {{event.title}}.

📅 Date: {{event.date}} at {{event.time}}
📍 Location: {{location.name}}
👥 Guests: {{attendee.guest_count}}
💰 Total: {{payment.total}}

Manage your registration: {{registration.manage_link}}

Best regards,
{{website.name}}
```

### **Campaign Email**  
```handlebars
Hello {{recipient.first_name}},

This is {{campaign.name}} from {{sender.name}}.

We hope you're enjoying your {{membership.type}} membership!

Unsubscribe: {{unsubscribe.link}}
```

## **Technical Implementation**

Your new system already supports this perfectly:

```php
// Works with both formats:
$old = "Hello {name}";                    // ← Backward compatible
$new = "Hello {{attendee.name}}";         // ← Recommended format

$result1 = SCT_Event_Email_Placeholders::replace_placeholders($old, $data);
$result2 = SCT_Event_Email_Placeholders::replace_placeholders($new, $data);
// Both produce: "Hello John Smith"
```

## **Action Items**

### **Immediate (Recommended)**
1. ✅ **Event admin UI updated** - Shows new format examples
2. ✅ **Documentation created** - Migration guide available  
3. ✅ **Backward compatibility** - Old format still works

### **Next Steps (Optional)**
1. **Update default templates** to use new format
2. **Add admin notices** encouraging migration
3. **Create template converter** tool

### **Long-term (Future)**
1. **Deprecation notices** for old format
2. **Admin UI improvements** with placeholder picker
3. **Advanced features** (conditionals, filters)

## **Conclusion**

**`{{category.property}}` format is definitively superior** for:
- ✅ **Industry compatibility**
- ✅ **Future extensibility** 
- ✅ **Visual clarity**
- ✅ **Organization**
- ✅ **Collision avoidance**

Your implementation already supports both formats perfectly, so you can migrate gradually while maintaining full backward compatibility.

**Recommendation: Start using `{{category.property}}` format for all new templates and encourage migration of existing ones when convenient.**
