<?php
/**
 * Email Utilities for SCT Event Administration
 * 
 * Centralizes email-related functions to eliminate duplicates within this plugin
 * while maintaining plugin independence.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the placeholder system
require_once dirname(__FILE__) . '/class-placeholder-system.php';

class SCT_Event_Email_Utilities {
    
    /**
     * Log email to database
     * 
     * @param int $event_id Event ID
     * @param string $email_type Type of email (confirmation, notification, mass_email, etc.)
     * @param string $recipient_email Recipient email address
     * @param string $subject Email subject
     * @param string $message Email message content
     * @param string $status Email status (sent, failed, pending)
     * @return int|false Insert ID on success, false on failure
     */
    public static function log_email($event_id, $email_type, $recipient_email, $subject, $message, $status = 'sent') {
        global $wpdb;
        
        $result = $wpdb->insert(
            "{$wpdb->prefix}sct_event_emails",
            array(
                'event_id'        => $event_id,
                'email_type'      => $email_type,
                'recipients'      => $recipient_email,
                'subject'         => $subject,
                'message'         => $message,
                'sent_date'       => current_time('mysql'),
                'status'          => $status,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Generate DKIM signature for email
     * 
     * @param string $subject Email subject
     * @param string $from_email From email address
     * @param string $to_email To email address  
     * @param string $body Email body content
     * @return string|false DKIM signature or false on failure
     */
    public static function generate_dkim_signature($subject, $from_email, $to_email, $body) {
        $private_key_path = EVENT_ADMIN_PATH . 'admin/keys/dkim_private.key';
        
        if (!file_exists($private_key_path)) {
            return false;
        }
        
        $private_key = file_get_contents($private_key_path);
        if (!$private_key) {
            return false;
        }
        
        // DKIM header construction
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $selector = 'default';
        $canonicalized_header = "from:{$from_email}\r\nto:{$to_email}\r\nsubject:{$subject}";
        $canonicalized_body = $body;
        
        // Create signature (simplified - in production use proper DKIM library)
        $signature = base64_encode(hash('sha256', $canonicalized_header . $canonicalized_body, true));
        
        return "DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d={$domain}; s={$selector}; b={$signature}";
    }
    
    /**
     * Replace email placeholders with actual values using the new standardized system
     * 
     * @param string $template Email template with placeholders
     * @param array $data Data array for placeholder replacement
     * @return string Processed email content
     */
    public static function replace_email_placeholders($template, $data) {
        // Use the new standardized placeholder system
        return SCT_Event_Email_Placeholders::replace_placeholders($template, $data);
    }
    
    /**
     * Format currency value (local implementation for email utilities)
     * 
     * @param float $amount Amount to format
     * @return string Formatted currency string
     */
    public static function format_currency($amount) {
        $currency_symbol = get_option('currency_symbol', '$');
        $currency_format = get_option('currency_format', 2);
        
        return $currency_symbol . number_format($amount, $currency_format);
    }
    
    /**
     * Get default email templates
     * 
     * @param string $template_type Type of template (confirmation, notification, etc.)
     * @return string Default template content
     */
    public static function get_default_template($template_type) {
        switch ($template_type) {
            case 'confirmation':
                return "Dear {{attendee.name}},\n\nThank you for registering for {{event.title}}.\n\nEvent Date: {{event.date}}\nTotal Cost: {{payment.total}}\nNumber of People: {{attendee.guest_count}}\n\nYou can manage your registration at: {{registration.manage_link}}\n\nBest regards,\n{{website.name}}";
                
            case 'notification':
                return "New registration for {{event.title}}:\n\nAttendee: {{attendee.name}} ({{attendee.email}})\nEvent Date: {{event.date}}\nTotal Cost: {{payment.total}}\nNumber of People: {{attendee.guest_count}}\n\nAdmin Dashboard: {{website.url}}/wp-admin/";
                
            case 'waiting_list':
                return "Dear {{attendee.name}},\n\nYou have been added to the waiting list for {{event.title}}.\n\nWe will notify you if a spot becomes available.\n\nBest regards,\n{{website.name}}";
                
            default:
                return "Hello {{attendee.name}},\n\nThank you for your interest in {{event.title}}.\n\nBest regards,\n{{website.name}}";
        }
    }
    
    /**
     * Send email with proper headers and logging
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @param array $headers Email headers
     * @param int $event_id Event ID for logging
     * @param string $email_type Email type for logging
     * @return bool Success/failure
     */
    public static function send_email($to, $subject, $message, $headers = array(), $event_id = 0, $email_type = 'general') {
        // Add DKIM signature if available
        $from_email = get_option('admin_email');
        $dkim_signature = self::generate_dkim_signature($subject, $from_email, $to, $message);
        
        if ($dkim_signature) {
            $headers[] = $dkim_signature;
        }
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Log email
        if ($event_id > 0) {
            self::log_email($event_id, $email_type, $to, $subject, $message, $sent ? 'sent' : 'failed');
        }
        
        return $sent;
    }
}
