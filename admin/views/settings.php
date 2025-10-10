<div class="wrap">
    <h1>Event Administration Settings</h1>
    <h2 class="nav-tab-wrapper">
        <a href="#general-settings" class="nav-tab nav-tab-active">General Settings</a>
        <a href="#email-settings" class="nav-tab">Email Settings</a>
        <a href="#custom-tables" class="nav-tab">Custom Tables</a>
    </h2>

    <form method="post" action="">
        <?php wp_nonce_field('save_sct_settings'); ?>

        <div id="general-settings" class="tab-content">
            <h2>General Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="event_registration_page">Event Registration Page</label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'event_registration_page',
                            'selected' => $sct_settings['event_registration_page'] ?? 0,
                            'show_option_none' => 'Select a page',
                            'option_none_value' => '0'
                        ));
                        ?>
                        <p class="description">Select the page where the event registration form is displayed.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="event_management_page">Event Management Page</label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'event_management_page',
                            'selected' => $sct_settings['event_management_page'] ?? 0,
                            'show_option_none' => 'Select a page',
                            'option_none_value' => '0'
                        ));
                        ?>
                        <p class="description">Select the page where the event management is displayed.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="admin_email">Admin Email</label>
                    </th>
                    <td>
                        <input type="email" 
                               id="admin_email" 
                               name="admin_email" 
                               value="<?php echo esc_attr($sct_settings['admin_email'] ?? get_bloginfo('admin_email')); ?>" 
                               class="regular-text">
                        <p class="description">Email address where registration notifications will be sent.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="currency">Currency</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="currency" 
                               name="currency" 
                               value="<?php echo esc_attr($sct_settings['currency'] ?? 'USD'); ?>" 
                               class="regular-text">
                        <input type="hidden" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr($sct_settings['currency_symbol'] ?? '$'); ?>">
                        <input type="hidden" id="currency_format" name="currency_format" value="<?php echo esc_attr($sct_settings['currency_format'] ?? 'symbol_before'); ?>">
                        <p class="description">Currency code (e.g., USD, EUR, GBP).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_of_week"><?php _e('Start of the week', 'sct-event-administration'); ?></label></th>
                    <td>
                        <select name="start_of_week" id="start_of_week">
                            <?php
                            $days = [
                                0 => __('Sunday'),
                                1 => __('Monday'),
                                2 => __('Tuesday'),
                                3 => __('Wednesday'),
                                4 => __('Thursday'),
                                5 => __('Friday'),
                                6 => __('Saturday'),
                            ];
                            $selected = $sct_settings['start_of_week'] ?? 1;
                            foreach ($days as $num => $label) {
                                echo '<option value="' . esc_attr($num) . '"' . selected($selected, $num, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php _e('Choose which day the calendar week should start on.', 'sct-event-administration'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="email-settings" class="tab-content" style="display: none;">
            <h2>Email Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="notification_subject">Admin Notification Subject</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="notification_subject" 
                               name="notification_subject" 
                               value="<?php echo esc_attr($sct_settings['notification_subject'] ?? 'New Event Registration'); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="notification_template">Admin Notification Template</label>
                    </th>
                    <td>
                        <textarea id="notification_template" 
                                  name="notification_template" 
                                  rows="10" 
                                  class="large-text code"><?php echo esc_textarea($sct_settings['notification_template'] ?? ''); ?></textarea>
                        
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php _e('Available Variables', 'sct-events'); ?></h2>
                            </div>
                            <div class="inside">
                                <div class="placeholder-category">
                                    <h4><?php _e('Registration Data', 'sct-events'); ?></h4>
                                    <div class="placeholder-list">
                                        <code class="variable-code">{{registration.name}}</code>
                                        <code class="variable-code">{{registration.email}}</code>
                                        <code class="variable-code">{{registration.guest_count}}</code>
                                        <code class="variable-code">{{registration.date}}</code>
                                    </div>
                                </div>
                                
                                <div class="placeholder-category">
                                    <h4><?php _e('Event Information', 'sct-events'); ?></h4>
                                    <div class="placeholder-list">
                                        <code class="variable-code">{{event.name}}</code>
                                        <code class="variable-code">{{event.date}}</code>
                                        <code class="variable-code">{{event.time}}</code>
                                        <code class="variable-code">{{event.location_name}}</code>
                                    </div>
                                </div>
                                
                                <p class="description">
                                    <em><?php _e('Note: Old format like {name} still works for backward compatibility', 'sct-events'); ?></em>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="confirmation_subject">Confirmation Email Subject</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="confirmation_subject" 
                               name="confirmation_subject" 
                               value="<?php echo esc_attr($sct_settings['confirmation_subject'] ?? 'Registration Confirmation'); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="confirmation_template">Confirmation Email Template</label>
                    </th>
                    <td>
                        <?php 
                        wp_editor(
                            $sct_settings['confirmation_template'] ?? '', // Load the current template
                            'confirmation_template', // Unique ID for the editor
                            array(
                                'textarea_name' => 'confirmation_template', // Name attribute for the form submission
                                'textarea_rows' => 15, // Number of rows for the editor
                                'media_buttons' => true, // Allow media uploads
                                'teeny' => false, // Use the full editor toolbar
                                'quicktags' => true, // Enable quicktags for HTML editing
                                'tinymce' => array(
                                    'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                                    'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                                    'content_css' => get_stylesheet_directory_uri() . '/editor-style.css', // Optional: Add custom editor styles
                                )
                            )
                        );
                        ?>
                        <div class="form-field">
                            <!-- Available Variables Postbox -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle"><?php _e('Available Variables', 'sct-events'); ?></h2>
                                </div>
                                <div class="inside">
                                    <div class="placeholder-category">
                                        <h4><?php _e('Registration Data', 'sct-events'); ?></h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{registration.name}}</code>
                                            <code class="variable-code">{{registration.email}}</code>
                                            <code class="variable-code">{{registration.guest_count}}</code>
                                            <code class="variable-code">{{registration.date}}</code>
                                            <code class="variable-code">{{registration.id}}</code>
                                            <code class="variable-code">{{registration.manage_link}}</code>
                                        </div>
                                    </div>
                                    
                                    <div class="placeholder-category">
                                        <h4><?php _e('Event Information', 'sct-events'); ?></h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{event.name}}</code>
                                            <code class="variable-code">{{event.date}}</code>
                                            <code class="variable-code">{{event.time}}</code>
                                            <code class="variable-code">{{event.description}}</code>
                                            <code class="variable-code">{{event.location_name}}</code>
                                            <code class="variable-code">{{event.location_link}}</code>
                                        </div>
                                    </div>
                                    
                                    <div class="placeholder-category">
                                        <h4><?php _e('Capacity & Payment', 'sct-events'); ?></h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{capacity.total}}</code>
                                            <code class="variable-code">{{capacity.remaining}}</code>
                                            <code class="variable-code">{{payment.total}}</code>
                                            <code class="variable-code">{{payment.status}}</code>
                                            <code class="variable-code">{{payment.type}}</code>
                                            <code class="variable-code">{{payment.method_details}}</code>
                                            <code class="variable-code">{{payment.pricing_overview}}</code>
                                        </div>
                                    </div>
                                    
                                    <div class="placeholder-category">
                                        <h4><?php _e('Website Information', 'sct-events'); ?></h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{website.name}}</code>
                                            <code class="variable-code">{{website.url}}</code>
                                            <code class="variable-code">{{date.current}}</code>
                                            <code class="variable-code">{{date.year}}</code>
                                        </div>
                                    </div>
                                    
                                    <p class="description">
                                        <em><?php _e('Note: Old format like {name} still works for backward compatibility', 'sct-events'); ?></em>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div id="custom-tables" class="tab-content" style="display: none;">
            <h2>Export Custom Tables</h2>
            <button id="export-custom-tables" class="button button-primary">Export Custom Tables</button>
        </div>

        <p class="submit">
            <input type="submit" 
                   name="submit_sct_settings" 
                   class="button button-primary" 
                   value="Save Settings">
            <button type="button" 
                    class="button" 
                    onclick="resetToDefaults()">Reset to Defaults</button>
        </p>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });

    // Add confirmation for resetting to defaults
    window.resetToDefaults = function() {
        if (confirm('Are you sure you want to reset email templates to default values? This will overwrite your current templates.')) {
            $('#notification_subject').val('New Event Registration: {event_name}');
            $('#confirmation_subject').val('Registration Confirmation: {event_name}');
            $('#notification_template').val(<?php echo json_encode($this->get_default_notification_template()); ?>);
            $('#confirmation_template').val(<?php echo json_encode($this->get_default_confirmation_template()); ?>);
        }
    };

    // Fetch and populate currency codes for autocomplete
    $.getJSON('https://gist.githubusercontent.com/ksafranski/2973986/raw/5fda5e87189b066e11c1bf80bbfbecb556cf2cc1/Common-Currency.json', function(data) {
        if (typeof data === 'object') {
            var currencyCodes = Object.keys(data).map(function(key) {
                return {
                    label: key + ' - ' + data[key].name,
                    format: data[key].decimal_digits,
                    symbol: data[key].symbol
                };
            });

            $('#currency').autocomplete({
                source: currencyCodes,
                select: function(event, ui) {
                    $('#currency').val(ui.item.label);
                    $('#currency_symbol').val(ui.item.symbol);
                    $('#currency_format').val(ui.item.format);
                    return false;
                }
            });
        } else {
            console.error('Unexpected data format:', data);
        }
    }).fail(function() {
        console.error('Failed to load currency codes.');
    });

    // Handle export custom tables button click
    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce
            },
            success: function(response) {
                if (response.success) {
                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = 'custom_tables_export.sql';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                } else {
                    alert('Failed to export tables: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while exporting tables. Please try again.');
                console.error('AJAX Error:', status, error);
            }
        });
    });
});
</script>
