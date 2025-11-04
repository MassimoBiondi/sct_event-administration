<?php
/**
 * Additional Information Fields Manager
 * 
 * Manages custom fields from event registrations and makes them available
 * as placeholders in email templates and admin displays.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCT_Additional_Fields_Manager {
    
    /**
     * Get all additional information fields for an event
     * 
     * @param int $event_id Event ID
     * @return array Array of field definitions with metadata
     */
    public static function get_event_fields($event_id) {
        global $wpdb;
        
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT comment_fields FROM {$wpdb->prefix}sct_events WHERE id = %d",
                $event_id
            )
        );
        
        if (!$event || empty($event->comment_fields)) {
            return array();
        }
        
        $fields = json_decode($event->comment_fields, true);
        return is_array($fields) ? $fields : array();
    }
    
    /**
     * Get field values for a specific registration
     * 
     * @param int $registration_id Registration ID
     * @return array Array of field values keyed by field ID
     */
    public static function get_registration_field_values($registration_id) {
        global $wpdb;
        
        $registration = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT comments FROM {$wpdb->prefix}sct_event_registrations WHERE id = %d",
                $registration_id
            )
        );
        
        if (!$registration || empty($registration->comments)) {
            return array();
        }
        
        $values = json_decode($registration->comments, true);
        return is_array($values) ? $values : array();
    }
    
    /**
     * Get field label by ID
     * 
     * @param int $event_id Event ID
     * @param string $field_id Field ID
     * @return string|null Field label or null if not found
     */
    public static function get_field_label($event_id, $field_id) {
        $fields = self::get_event_fields($event_id);
        
        foreach ($fields as $field) {
            if ($field['id'] === $field_id) {
                return $field['label'] ?? $field_id;
            }
        }
        
        return null;
    }
    
    /**
     * Format field value for display
     * 
     * @param mixed $value Field value
     * @param string $field_type Field type (textarea, text, checkbox, select)
     * @return string Formatted value
     */
    public static function format_field_value($value, $field_type = 'textarea') {
        if (empty($value)) {
            return '—';
        }
        
        switch ($field_type) {
            case 'checkbox':
                return $value ? 'Yes' : 'No';
                
            case 'textarea':
                // Add line breaks for email display
                return nl2br(esc_html($value));
                
            case 'text':
            case 'select':
            default:
                return esc_html($value);
        }
    }
    
    /**
     * Get all additional fields formatted for email display
     * 
     * @param int $event_id Event ID
     * @param int $registration_id Registration ID
     * @return string HTML formatted string with all additional fields
     */
    public static function format_for_email($event_id, $registration_id) {
        $fields = self::get_event_fields($event_id);
        $values = self::get_registration_field_values($registration_id);
        
        if (empty($fields) || empty($values)) {
            return '';
        }
        
        $output = '<div style="border-top: 2px solid #e0e0e0; margin-top: 20px; padding-top: 20px;">' . "\n";
        $output .= '<h3 style="color: #333; font-size: 16px; margin-bottom: 15px;">Additional Information</h3>' . "\n";
        
        foreach ($fields as $field) {
            $field_id = $field['id'];
            
            if (isset($values[$field_id])) {
                $label = $field['label'] ?? $field_id;
                $value = self::format_field_value($values[$field_id], $field['type']);
                
                $output .= '<div style="margin-bottom: 15px;">' . "\n";
                $output .= '<strong style="display: block; margin-bottom: 5px; color: #555;">' . esc_html($label) . ':</strong>' . "\n";
                $output .= '<p style="margin: 0; color: #666; line-height: 1.6;">' . $value . '</p>' . "\n";
                $output .= '</div>' . "\n";
            }
        }
        
        $output .= '</div>' . "\n";
        
        return $output;
    }
    
    /**
     * Get all additional fields formatted as plain text for email
     * 
     * @param int $event_id Event ID
     * @param int $registration_id Registration ID
     * @return string Plain text formatted string
     */
    public static function format_for_email_text($event_id, $registration_id) {
        $fields = self::get_event_fields($event_id);
        $values = self::get_registration_field_values($registration_id);
        
        if (empty($fields) || empty($values)) {
            return '';
        }
        
        $output = "\n----------------------------------------\n";
        $output .= "ADDITIONAL INFORMATION\n";
        $output .= "----------------------------------------\n\n";
        
        foreach ($fields as $field) {
            $field_id = $field['id'];
            
            if (isset($values[$field_id])) {
                $label = $field['label'] ?? $field_id;
                $value = $values[$field_id];
                
                if (empty($value)) {
                    $value = '[Not provided]';
                } elseif ($field['type'] === 'checkbox') {
                    $value = $value ? 'Yes' : 'No';
                }
                
                $output .= $label . ":\n";
                $output .= $value . "\n\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Get field metadata for admin display
     * 
     * @param int $event_id Event ID
     * @return array Array with field metadata for display
     */
    public static function get_field_metadata($event_id) {
        $fields = self::get_event_fields($event_id);
        $metadata = array();
        
        foreach ($fields as $field) {
            $metadata[$field['id']] = array(
                'label' => $field['label'] ?? $field['id'],
                'type' => $field['type'] ?? 'textarea',
                'required' => $field['required'] ?? false,
                'placeholder' => $field['placeholder'] ?? '',
            );
        }
        
        return $metadata;
    }
    
    /**
     * Register additional fields as email placeholders
     * 
     * @param int $event_id Event ID
     * @param array $placeholder_registry Reference to placeholder registry
     */
    public static function register_field_placeholders($event_id, &$placeholder_registry) {
        $fields = self::get_event_fields($event_id);
        
        foreach ($fields as $field) {
            $field_id = $field['id'];
            $placeholder_key = 'additional.' . $field_id;
            
            // Register placeholder for this field
            if (!isset($placeholder_registry[$placeholder_key])) {
                $placeholder_registry[$placeholder_key] = array(
                    'description' => $field['label'] ?? 'Additional field: ' . $field_id,
                    'category' => 'additional',
                    'example' => '[User response]',
                    'required_data' => array('comments', 'event_id')
                );
            }
        }
    }
    
    /**
     * Validate additional fields in registration
     * 
     * @param int $event_id Event ID
     * @param array $field_values Submitted field values
     * @return array Array of validation errors (empty if valid)
     */
    public static function validate_fields($event_id, $field_values) {
        $fields = self::get_event_fields($event_id);
        $errors = array();
        
        foreach ($fields as $field) {
            $field_id = $field['id'];
            
            if ($field['required'] && (empty($field_values[$field_id]) || trim($field_values[$field_id]) === '')) {
                $errors[$field_id] = $field['label'] . ' is required.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get summary of all additional fields for an event
     * 
     * @param int $event_id Event ID
     * @return array Summary data for admin display
     */
    public static function get_event_summary($event_id) {
        $fields = self::get_event_fields($event_id);
        
        return array(
            'total_fields' => count($fields),
            'required_fields' => count(array_filter($fields, function($f) { return $f['required'] ?? false; })),
            'field_types' => array_count_values(array_map(function($f) { return $f['type']; }, $fields)),
            'fields' => $fields,
        );
    }
    
    /**
     * Export field data for a registration as CSV-compatible array
     * 
     * @param int $event_id Event ID
     * @param int $registration_id Registration ID
     * @return array Array of field data suitable for CSV export
     */
    public static function export_for_csv($event_id, $registration_id) {
        $fields = self::get_event_fields($event_id);
        $values = self::get_registration_field_values($registration_id);
        $export = array();
        
        foreach ($fields as $field) {
            $field_id = $field['id'];
            $label = $field['label'] ?? $field_id;
            
            $value = $values[$field_id] ?? '';
            
            // Normalize value for CSV
            if ($field['type'] === 'checkbox') {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            // Remove line breaks for CSV
            $value = str_replace(array("\r", "\n"), ' ', $value);
            
            $export[$label] = $value;
        }
        
        return $export;
    }
}
