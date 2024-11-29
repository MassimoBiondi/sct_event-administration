<div class="wrap">
    <h1>Settings</h1>
    <?php settings_errors(); ?>
    <form method="post" action="">
        <?php wp_nonce_field('save_sct_settings'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="event_registration_page">Event Registration Page</label>
                </th>
                <td>
                    <?php
                        wp_dropdown_pages(array(
                            'name' => 'event_registration_page',
                            'post_status' => 'draft,publish,pending',
                            'selected' => $sct_settings['event_registration_page'],
                            'show_option_none' => 'Select a page'
                        ));
                        echo '<p class="description">Select the page where you want to display event registrations. Add the shortcode [event_registration id="X"] to this page.</p>';
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="admin_email">Admin Notification Email</label>
                </th>
                <td>
                    <input type="email" 
                           id="admin_email" 
                           name="admin_email" 
                           value="<?php echo $sct_settings['admin_email'] ? esc_attr($sct_settings['admin_email']) : get_bloginfo('admin_email'); ?>" 
                           class="regular-text">
                    <p class="description">Email address where registration notifications will be sent.</p>
                </td>
            </tr>

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
    // Add confirmation for resetting to defaults
    window.resetToDefaults = function() {
        if (confirm('Are you sure you want to reset email templates to default values? This will overwrite your current templates.')) {
            $('#notification_subject').val('New Event Registration: {event_name}');
            $('#confirmation_subject').val('Registration Confirmation: {event_name}');
            $('#notification_template').val(<?php echo json_encode($this->get_default_notification_template()); ?>);
            $('#confirmation_template').val(<?php echo json_encode($this->get_default_confirmation_template()); ?>);
        }
    };
});
</script>
