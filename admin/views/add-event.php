<?php
    $event = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        global $wpdb;
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            intval($_GET['id'])
        ));
    }
    $sct_settings = get_option('event_admin_settings');
?>
<div class="wrap">
    <h1><?php echo $event ? 'Edit Event' : 'Add New Event'; ?></h1>
    
    <form id="add-event-form" class="event-form" accept-charset="utf-8">
        <input type="hidden" name="action" value="save_event">
        <?php if ($event): ?>
            <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">
        <?php endif; ?>
        <?php wp_nonce_field('event_admin_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="event_name">Event Name</label></th>
                <td>
                    <input type="text" 
                           id="event_name" 
                           name="event_name" 
                           class="regular-text"
                           value="<?php echo $event ? esc_attr($event->event_name) : ''; ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="event_date">Event Date</label></th>
                <td>
                    <input type="date" 
                           id="event_date" 
                           name="event_date"
                           value="<?php echo $event ? esc_attr($event->event_date) : ''; ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="event_time">Event Time</label></th>
                <td>
                    <input type="time" 
                           id="event_time" 
                           name="event_time"
                           value="<?php echo $event ? esc_attr($event->event_time) : ''; ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="location_name">Location Name</label></th>
                <td>
                    <input type="text" 
                           id="location_name" 
                           name="location_name" 
                           class="regular-text"
                           value="<?php echo $event ? esc_attr($event->location_name) : ''; ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="location_link">Location Link</label></th>
                <td>
                    <input type="url" 
                           id="location_link" 
                           name="location_link" 
                           class="regular-text"
                           value="<?php echo $event ? esc_attr($event->location_link) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="event_description">Description</label></th>
                <td>
                    <?php 
                    wp_editor(
                        $event ? htmlspecialchars_decode($event->description) : '',
                        'event_description',
                        array(
                            'textarea_name' => 'event_description',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny' => false,
                            'quicktags' => true,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                                'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                            )
                        )
                    );
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="guest_capacity">Guest Capacity</label></th>
                <td>
                    <input type="number" 
                           id="guest_capacity" 
                           name="guest_capacity" 
                           min="0"
                           value="<?php echo $event ? esc_attr($event->guest_capacity) : 0; ?>"
                           required>
                            <span class="description">Set to 0 for unlimited.</span>
                </td>
            </tr>
            <tr>
                <th><label for="max_guests_per_registration">Max Guests/Registration:</label></th>
                <td>
                <input type="number" 
                            id="max_guests_per_registration" 
                            name="max_guests_per_registration" 
                            value="<?php echo esc_attr($event->max_guests_per_registration ?? 0); ?>" 
                            min="0" 
                            required>
                            <span class="description">Set to 0 for unlimited.</span>
                </td>
            </tr>
            <tr>
                <th><label for="admin_email">Admin Email</label></th>
                <td>
                    <input type="email" 
                           id="admin_email" 
                           name="admin_email" 
                           class="regular-text"
                           value="<?php echo $event->admin_email ? esc_attr($event->admin_email) : get_bloginfo('admin_email'); ?>"
                           required>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" 
                   class="button button-primary" 
                   value="<?php echo $event ? 'Update Event' : 'Add Event'; ?>">
            <a href="<?php echo admin_url('admin.php?page=event-admin'); ?>" 
               class="button">Cancel</a>
        </p>
    </form>
</div>
