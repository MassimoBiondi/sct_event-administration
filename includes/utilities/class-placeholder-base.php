<?php
/**
 * Base Email Placeholder System
 * 
 * Provides a standardized, extensible placeholder system for email templates.
 * This abstract base class ensures uniform behavior across all SCT plugins
 * while maintaining plugin independence.
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class SCT_Email_Placeholder_Base {
    
    /**
     * Registry of all registered placeholders
     * @var array
     */
    protected static $placeholders = array();
    
    /**
     * Default values for placeholders
     * @var array
     */
    protected static $defaults = array();
    
    /**
     * Whether the plugin placeholders have been registered
     * @var bool
     */
    protected static $initialized = false;
    
    /**
     * Initialize the placeholder system
     */
    protected static function initialize() {
        if (!static::$initialized) {
            static::register_universal_placeholders();
            static::register_plugin_placeholders();
            static::$initialized = true;
        }
    }
    
    /**
     * Register universal placeholders available in all plugins
     */
    protected static function register_universal_placeholders() {
        // Website placeholders
        static::register_placeholder('website.name', array(
            'description' => 'Website name',
            'callback' => function($data) { return get_bloginfo('name'); },
            'category' => 'website',
            'example' => 'My Website',
            'required_data' => array()
        ));
        
        static::register_placeholder('website.url', array(
            'description' => 'Website URL',
            'callback' => function($data) { return home_url(); },
            'category' => 'website',
            'example' => 'https://example.com',
            'required_data' => array()
        ));
        
        // Date placeholders
        static::register_placeholder('date.current', array(
            'description' => 'Current date',
            'callback' => function($data) { return current_time('F j, Y'); },
            'category' => 'date',
            'example' => 'August 11, 2025',
            'required_data' => array()
        ));
        
        static::register_placeholder('date.year', array(
            'description' => 'Current year',
            'callback' => function($data) { return current_time('Y'); },
            'category' => 'date',
            'example' => '2025',
            'required_data' => array()
        ));
        
        // Admin placeholders
        static::register_placeholder('admin.email', array(
            'description' => 'Admin email address',
            'callback' => function($data) { return get_option('admin_email'); },
            'category' => 'admin',
            'example' => 'admin@example.com',
            'required_data' => array()
        ));
    }
    
    /**
     * Register plugin-specific placeholders (must be implemented by child classes)
     */
    abstract protected static function register_plugin_placeholders();
    
    /**
     * Register a new placeholder
     * 
     * @param string $key Placeholder key (e.g., 'user.name')
     * @param array $config Placeholder configuration
     */
    protected static function register_placeholder($key, $config) {
        static::$placeholders[$key] = array_merge(array(
            'description' => '',
            'callback' => null,
            'category' => 'general',
            'example' => '',
            'required_data' => array(),
        ), $config);
    }
    
    /**
     * Set default value for a placeholder
     * 
     * @param string $key Placeholder key
     * @param string $default Default value
     */
    protected static function set_default_value($key, $default) {
        static::$defaults[$key] = $default;
    }
    
    /**
     * Replace placeholders in template
     * 
     * @param string $template Template with placeholders
     * @param array $data Data for replacement
     * @return string Processed template
     */
    public static function replace_placeholders($template, $data = array()) {
        static::initialize();
        
        // Handle both old {placeholder} and new {{placeholder}} formats
        $template = static::process_old_format($template, $data);
        $template = static::process_new_format($template, $data);
        
        return $template;
    }
    
    /**
     * Process old format placeholders {key}
     * 
     * @param string $template Template content
     * @param array $data Data array
     * @return string Processed template
     */
    protected static function process_old_format($template, $data) {
        // Simple mapping of old format to new format for backward compatibility
        $old_mappings = static::get_old_format_mappings();
        
        foreach ($old_mappings as $old => $new) {
            if (strpos($template, $old) !== false) {
                $value = static::replace_single_placeholder($new, $data);
                $template = str_replace($old, $value, $template);
            }
        }
        
        return $template;
    }
    
    /**
     * Get mappings from old format to new format (override in child classes)
     */
    protected static function get_old_format_mappings() {
        return array(
            '{site_name}' => 'website.name',
            '{site_url}' => 'website.url',
            '{current_date}' => 'date.current',
            '{admin_email}' => 'admin.email'
        );
    }
    
    /**
     * Process new format placeholders {{category.key}}
     * 
     * @param string $template Template content
     * @param array $data Data array
     * @return string Processed template
     */
    protected static function process_new_format($template, $data) {
        // Match {{category.key}} patterns
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($data) {
            $placeholder = $matches[1];
            return static::replace_single_placeholder($placeholder, $data);
        }, $template);
    }
    
    /**
     * Replace a single placeholder
     * 
     * @param string $key Placeholder key
     * @param array $data Data array
     * @return string Replacement value
     */
    protected static function replace_single_placeholder($key, $data) {
        static::initialize();
        
        if (!isset(static::$placeholders[$key])) {
            // Return placeholder unchanged if not found (for debugging)
            return '{{' . $key . '}}';
        }
        
        $placeholder = static::$placeholders[$key];
        
        // Check if required data is available
        if (!empty($placeholder['required_data'])) {
            foreach ($placeholder['required_data'] as $required_field) {
                if (!isset($data[$required_field]) || empty($data[$required_field])) {
                    // Return default value if available
                    if (isset(static::$defaults[$key])) {
                        return static::$defaults[$key];
                    }
                    // Return placeholder with indication of missing data
                    return '[' . ucwords(str_replace('.', ' ', $key)) . ']';
                }
            }
        }
        
        // Execute callback to get value
        if (is_callable($placeholder['callback'])) {
            try {
                $value = call_user_func($placeholder['callback'], $data);
                return $value !== null ? $value : (static::$defaults[$key] ?? '');
            } catch (Exception $e) {
                error_log('Placeholder callback error for ' . $key . ': ' . $e->getMessage());
                return static::$defaults[$key] ?? '[Error: ' . $key . ']';
            }
        }
        
        return static::$defaults[$key] ?? '';
    }
    
    /**
     * Get all registered placeholders
     * 
     * @return array All placeholders with their configuration
     */
    public static function get_all_placeholders() {
        static::initialize();
        return static::$placeholders;
    }
    
    /**
     * Get placeholders by category
     * 
     * @param string $category Category name
     * @return array Placeholders in the specified category
     */
    public static function get_placeholders_by_category($category = null) {
        static::initialize();
        
        if ($category === null) {
            $grouped = array();
            foreach (static::$placeholders as $key => $config) {
                $grouped[$config['category']][$key] = $config;
            }
            return $grouped;
        }
        
        $filtered = array();
        foreach (static::$placeholders as $key => $config) {
            if ($config['category'] === $category) {
                $filtered[$key] = $config;
            }
        }
        return $filtered;
    }
    
    /**
     * Validate template placeholders
     * 
     * @param string $template Template to validate
     * @return array Validation results
     */
    public static function validate_template($template) {
        static::initialize();
        
        $results = array(
            'valid' => array(),
            'invalid' => array(),
            'warnings' => array()
        );
        
        // Find all {{placeholder}} patterns
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        
        foreach ($matches[1] as $placeholder) {
            if (isset(static::$placeholders[$placeholder])) {
                $results['valid'][] = $placeholder;
            } else {
                $results['invalid'][] = $placeholder;
            }
        }
        
        // Find old format placeholders
        preg_match_all('/\{([^}]+)\}/', $template, $old_matches);
        foreach ($old_matches[1] as $old_placeholder) {
            if (!in_array($old_placeholder, $matches[1])) { // Not already found as new format
                $results['warnings'][] = 'Old format placeholder found: {' . $old_placeholder . '}';
            }
        }
        
        return $results;
    }
    
    /**
     * Get documentation for placeholders
     * 
     * @param string $format Output format ('html', 'text', 'array')
     * @return string|array Documentation
     */
    public static function get_documentation($format = 'html') {
        static::initialize();
        
        $categories = static::get_placeholders_by_category();
        
        if ($format === 'array') {
            return $categories;
        }
        
        $output = '';
        foreach ($categories as $category => $placeholders) {
            if ($format === 'html') {
                $output .= "<h3>" . ucwords($category) . " Placeholders</h3>\n<ul>\n";
                foreach ($placeholders as $key => $config) {
                    $output .= "<li><strong>{{" . $key . "}}</strong> - " . $config['description'];
                    if (!empty($config['example'])) {
                        $output .= " <em>(Example: " . $config['example'] . ")</em>";
                    }
                    $output .= "</li>\n";
                }
                $output .= "</ul>\n";
            } else {
                $output .= strtoupper($category) . " PLACEHOLDERS:\n";
                foreach ($placeholders as $key => $config) {
                    $output .= "  {{" . $key . "}} - " . $config['description'];
                    if (!empty($config['example'])) {
                        $output .= " (Example: " . $config['example'] . ")";
                    }
                    $output .= "\n";
                }
                $output .= "\n";
            }
        }
        
        return $output;
    }
}
