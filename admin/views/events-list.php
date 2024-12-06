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
                <!-- <th class="check-column"></th> -->
                <th class="collapse">Event Name</th>
                <th class="collapse">Description</th>
                <th class="collapse column-small">Date</th>
                <th class="collapse column-small">Time</th>
                <th class="collapse">Location</th>
                <th class="collapse column-small">Capacity</th>
                <th class="collapse column-small">Registered</th>
                <th class="collapse column-small">Available</th>
                <th class="collapse column-small">Max Guest / Registration</th>
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
                    $available = $event->guest_capacity - $registered;
            ?>
                <tr>
                    </td>
                    <td class="collapse">
                        <?php
                            echo sprintf('<span class="status-indicator status-%s">%s</span>',
                                ($event->guest_capacity > 0 && $available != 0 ) ? 'success' : 'failed',
                                esc_html($event->event_name)
                            );
                            // echo esc_html($event->event_name);
                        ?>
                    </td>
                    <td class="collapse"><?php echo esc_html($event->description); ?></td>
                    <td class="collapse"><?php echo esc_html(date('Y-m-d', strtotime($event->event_date))); ?></td>
                    <td class="collapse"><?php echo esc_html(date('H:i', strtotime($event->event_time))); ?></td>
                    <td class="collapse">
                        <?php if ($event->location_link): ?>
                            <a href="<?php echo esc_url($event->location_link); ?>" target="_blank">
                                <?php echo esc_html($event->location_name); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($event->location_name); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($event->guest_capacity > 0 ? $event->guest_capacity : 'Unlimited'); ?></td>
                    <td><a href="admin.php?page=event-registrations&event_id=<?php echo $event->id; ?>"><?php echo esc_html($registered); ?></a></td>
                    <td>
                        <?php //status-indicator status-failed
                            echo sprintf('<span class="status-indicator status-%s">%s</span>',
                                ($event->guest_capacity > 0 && $available != 0 ) ? 'success' : 'failed',
                                $available
                            );
                        ?>
                    </td>
                    <td><?php echo esc_html($event->max_guests_per_registration > 0 ? $event->max_guests_per_registration : 'Unlimited');?></td>
                    <td class="collapse"><?php echo explode('@', $event->admin_email)[0];?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=event-admin-new&action=edit&id=' . $event->id); ?>" 
                           class="button button-small">
                           Edit
                        </a>
                            <span class="trash">
                                <a class="delete-event" 
                                    data-event-id="<?php echo esc_attr($event->id); ?>" 
                                    data-nonce="<?php echo wp_create_nonce('delete_event'); ?>">
                                    Delete
                                </a>
                            </span>
                            <!-- <button class="delete-event button button-small trash" 
                                    data-event-id="<?php echo esc_attr($event->id); ?>" 
                                    data-nonce="<?php echo wp_create_nonce('delete_event'); ?>">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 5px;"></span>
                                Delete
                            </button> -->
                    </td>
                </tr>
            <?php 
                endforeach;
            else:
            ?>
                <tr>
                    <td colspan="8">No events found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
