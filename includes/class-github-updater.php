<?php
/**
 * GitHub Updater Class
 * 
 * Allows WordPress plugins to be updated from GitHub repositories
 */

if (!defined('ABSPATH')) exit;

class SCT_GitHub_Updater {
    private $plugin_file;
    private $plugin_data;
    private $username;
    private $repository;
    private $access_token;
    private $plugin_activated;

    public function __construct($plugin_file, $username, $repository, $access_token = '') {
        $this->plugin_file = $plugin_file;
        $this->plugin_data = get_plugin_data($plugin_file);
        $this->username = $username;
        $this->repository = $repository;
        $this->access_token = $access_token;
        $this->plugin_activated = is_plugin_active(plugin_basename($plugin_file));

        if ($this->plugin_activated) {
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
            add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
            add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        }
    }

    /**
     * Get information regarding our plugin from GitHub
     */
    private function get_repository_info() {
        $transient_key = 'sct_github_update_' . md5($this->username . '/' . $this->repository);
        
        if (false === ($repository_data = get_transient($transient_key))) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository);
            
            $args = array();
            if (!empty($this->access_token)) {
                $args['headers'] = array(
                    'Authorization' => 'token ' . $this->access_token
                );
            }

            $response = wp_remote_get($request_uri, $args);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $repository_data = json_decode(wp_remote_retrieve_body($response), true);
                set_transient($transient_key, $repository_data, 3600); // Cache for 1 hour
            } else {
                return false;
            }
        }

        return $repository_data;
    }

    /**
     * Check if there's an update available
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $plugin_slug = plugin_basename($this->plugin_file);
        
        if (!isset($transient->checked[$plugin_slug])) {
            return $transient;
        }

        $repository_data = $this->get_repository_info();
        
        if (!$repository_data) {
            return $transient;
        }

        $current_version = $this->plugin_data['Version'];
        $remote_version = ltrim($repository_data['tag_name'], 'v');

        if (version_compare($current_version, $remote_version, '<')) {
            $transient->response[$plugin_slug] = (object) array(
                'slug' => dirname($plugin_slug),
                'plugin' => $plugin_slug,
                'new_version' => $remote_version,
                'url' => $this->plugin_data['PluginURI'],
                'package' => $repository_data['zipball_url'],
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => get_bloginfo('version'),
                'requires_php' => false,
                'compatibility' => new stdClass(),
            );
        }

        return $transient;
    }

    /**
     * Show plugin information popup
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== dirname(plugin_basename($this->plugin_file))) {
            return $result;
        }

        $repository_data = $this->get_repository_info();
        
        if (!$repository_data) {
            return $result;
        }

        return (object) array(
            'name' => $this->plugin_data['Name'],
            'slug' => dirname(plugin_basename($this->plugin_file)),
            'version' => ltrim($repository_data['tag_name'], 'v'),
            'author' => $this->plugin_data['AuthorName'],
            'author_profile' => $this->plugin_data['AuthorURI'],
            'requires' => false,
            'tested' => get_bloginfo('version'),
            'requires_php' => false,
            'sections' => array(
                'description' => $this->plugin_data['Description'],
                'changelog' => $repository_data['body'],
            ),
            'short_description' => $this->plugin_data['Description'],
            'download_link' => $repository_data['zipball_url'],
        );
    }

    /**
     * Perform additional actions to successfully install our plugin
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->plugin_file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->plugin_activated) {
            activate_plugin(plugin_basename($this->plugin_file));
        }

        return $result;
    }

    /**
     * Check for updates manually (for admin interface)
     */
    public function force_check() {
        delete_transient('sct_github_update_' . md5($this->username . '/' . $this->repository));
        return $this->get_repository_info();
    }
}