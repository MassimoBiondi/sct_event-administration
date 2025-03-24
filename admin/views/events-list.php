<div class="wrap">
    <h1 class="wp-heading-inline">Events</h1>
    <a href="<?php echo admin_url('admin.php?page=event-admin-new'); ?>" class="page-title-action">Add New Event</a>
    
    <div id="event-admin-message" class="notice is-dismissible" hidden> <!-- notice-success -->
        <p><strong class="alert-message">Settings saved.</strong></p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
    </div>
    
    <hr class="wp-header-end">
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th class="collapse check-column"></th>
                <th class="collapse check-column"></th>
                <th class="collapse">Event Name</th>
                <!-- <th class="collapse">Description</th> -->
                <th class="collapse column-small">Date</th>
                <th class="collapse column-small">Time</th>
                <th class="collapse">Location</th>
                <th class="collapse column-small center">Capacity</th>
                <th class="collapse column-small center">Registered</th>
                <th class="collapse column-small center">Available</th>
                <th class="collapse column-small center">Max Guest / Registration</th>
                <th class="collapse column-small">Organizer</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($events)):
                foreach ($events as $event): 
                    $registered = $wpdb->get_var($wpdb->prepare(
                        "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
                        $event->id
                    ));
                    $registered = $registered ?: 0;
                    $available = ($event->guest_capacity > 0) ? $event->guest_capacity - $registered : 'n/a';
                    $max_guests_per_registration = ($event->max_guests_per_registration > 0) ? $event->max_guests_per_registration : 'n/a';
                    $registration_page_id = get_option('event_admin_settings', [])["event_registration_page"];
                    $managemnt_page_id = get_option('event_admin_settings', [])["event_management_page"];
                    $registration_url = add_query_arg('id', $event->id, get_permalink($registration_page_id));

                    // Create an object with additional properties
                    $event_data = (object) array_merge((array) $event, [
                        'registered' => $registered,
                        'available' => $available
                    ]);
            ?>
                <tr>
                    <td class="">
                        <?php if ($event->member_only): ?>
                            <span class="dashicons dashicons-admin-users"></span>
                        <?php endif; ?>
                    </td>
                    <td class="">
                        <?php if ($event->by_lottery): ?>
                            <span class="dashicons dashicons-tickets-alt"></span>
                        <?php endif; ?>
                    </td>
                    <td class="collapse">
                        <?php
                            echo sprintf('<span class="status-indicator status-%s">%s</span>',
                                ($event->guest_capacity == 0 || $available != 0 ) ? 'success' : 'failed',
                                esc_html($event->event_name)
                            );
                        ?>
                    </td>
                    <!-- <td><?php echo esc_html($event->description); ?></td> -->
                    <td class="collapse column-small"><?php echo esc_html($event->event_date); ?></td>
                    <td class="collapse column-small">
                        <?php if ($event->event_time == '00:00:00'): ?>
                            &nbsp;
                        <?php else: ?>
                            </span><?php echo esc_html($event->event_time); ?>
                        <?php endif; ?>
                    </td>
                    <td class="collapse"><?php echo esc_html($event->location_name); ?></td>
                    <td class="collapse column-small center"><?php echo ($event->guest_capacity > 0) ? esc_html($event->guest_capacity) : 'n/a'; ?></td>
                    <td class="collapse column-small center"><?php echo esc_html($registered); ?></td>
                    <td class="collapse column-small center"><?php echo ($event->guest_capacity > 0) ? esc_html($available) : 'n/a'; ?></td>
                    <td class="collapse column-small center"><?php echo ($event->max_guests_per_registration > 0) ? esc_html($event->max_guests_per_registration) : 'n/a'; ?></td>
                    <td class="collapse"><?php echo explode('@', $event->admin_email)[0];?></td>
                    <td class="right">
                        <a class="event-details" 
                            data-event='<?php echo json_encode($event_data); ?>'>
                           <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=event-admin-new&action=edit&id=' . $event->id); ?>">
                           <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a class="copy-url" 
                            data-url="<?php echo esc_url($registration_url); ?>">
                           <span class="dashicons dashicons-admin-links"></span>
                        </a>
                        <span class="trash">
                            <a class="delete-event" 
                               data-event-id="<?php echo esc_attr($event->id); ?>" 
                               data-nonce="<?php echo wp_create_nonce('delete_event'); ?>">
                               <span class="dashicons dashicons-trash"></span>
                            </a>
                        </span>
                    </td>
                </tr>
            <?php 
                endforeach;
            else:
            ?>
                <tr>
                    <td colspan="11">No events found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Event Details Modal -->
<div id="event-details-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close-modal">&times;</span>
            <h2>Event Details</h2>
        </div>
        <div class="modal-body">
            <div id="event-details"></div>
        </div>
    </div>
</div>
