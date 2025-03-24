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
                            'selected' => $sct_settings['event_registration_page'],
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
                            'selected' => $sct_settings['event_management_page'],
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
                               value="<?php echo esc_attr($sct_settings['admin_email']); ?>" 
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
                               value="<?php echo esc_attr($sct_settings['currency']); ?>" 
                               class="regular-text">
                        Symbol <input type="hidden" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr($sct_settings['currency_symbol']); ?>">
                        Format <input type="hidden" id="currency_format" name="currency_format" value="<?php echo esc_attr($sct_settings['currency_format']); ?>">
                        <p class="description">Currency code (e.g., USD, EUR, GBP).</p>
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
                               value="<?php echo esc_attr($sct_settings['notification_subject']); ?>" 
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
                                  class="large-text code"><?php echo esc_textarea($sct_settings['notification_template']); ?></textarea>
                        <p class="description">
                            Available placeholders: {event_name}, {name}, {email}, {guest_count}, 
                            {registration_date}, {event_date}, {event_time}, {location_name}
                        </p>
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
                               value="<?php echo esc_attr($sct_settings['confirmation_subject']); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="confirmation_template">Confirmation Email Template</label>
                    </th>
                    <td>
                        <textarea id="confirmation_template" 
                                  name="confirmation_template" 
                                  rows="10" 
                                  class="large-text code"><?php echo esc_textarea($sct_settings['confirmation_template']); ?></textarea>
                        <p class="description">
                            Available placeholders: {event_name}, {name}, {email}, {guest_count}, 
                            {event_date}, {event_time}, {location_name}
                        </p>
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
