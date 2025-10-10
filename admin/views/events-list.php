<div class="wrap">
    <h1 class="wp-heading-inline">Events</h1>
    <a href="<?php echo admin_url('admin.php?page=event-admin-new'); ?>" class="page-title-action">Add New Event</a>
    <button id="copy-event-button" class="page-title-action">Copy Event</button>


    <div id="event-admin-message" class="notice is-dismissible" hidden>
        <p><strong class="alert-message">Settings saved.</strong></p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
    </div>

    <hr class="wp-header-end">
    <div class="uk-overflow-auto">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="collapse check-column"></th>
                    <th class="collapse check-column"></th>
                    <th class="collapse">Event Name</th>
                    <th class="collapse column-small">Date</th>
                    <th class="collapse column-small">Time</th>
                    <th class="collapse">Location</th>
                    <th class="collapse column-small center">Type</th>
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
                        $registration_page_id = get_option('event_admin_settings', [])["event_registration_page"] ?? 0;
                        $managemnt_page_id = get_option('event_admin_settings', [])["event_management_page"] ?? 0;
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
                                <img src="<?php echo plugin_dir_url(__DIR__) . 'css/img/member.svg'; ?>" alt="Lottery" class="lottery-icon" style="width: 20px; height: 20px;">
                                <!-- <span class="dashicons dashicons-admin-users"></span> -->
                            <?php endif; ?>
                        </td>
                        <td class="">
                            <?php if ($event->by_lottery): ?>
                                <img src="<?php echo plugin_dir_url(__DIR__) . 'css/img/lottery_wheel.svg'; ?>" alt="Lottery" class="lottery-icon" style="width: 20px; height: 20px;">
                                <!-- <span class="dashicons dashicons-tickets-alt"></span> -->
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
                        <td class="collapse column-small"><?php echo esc_html($event->event_date); ?></td>
                        <td class="collapse column-small">
                            <?php if ($event->event_time == '00:00:00'): ?>
                                &nbsp;
                            <?php else: ?>
                                </span><?php echo esc_html($event->event_time); ?>
                            <?php endif; ?>
                        </td>
                        <td class="collapse"><?php echo esc_html(stripslashes($event->location_name)); ?></td>
                        <td class="collapse column-small center">
                            <?php echo (isset($event->external_registration) && $event->external_registration) ? 
                                '<span class="dashicons dashicons-external" title="External Registration"></span>' : 
                                '<span class="dashicons dashicons-groups" title="Internal Registration"></span>'; ?>
                        </td>
                        <td class="collapse column-small center"><?php echo ($event->guest_capacity > 0) ? esc_html($event->guest_capacity) : 'n/a'; ?></td>
                        <td class="collapse column-small center"><?php echo esc_html($registered); ?></td>
                        <td class="collapse column-small center"><?php echo ($event->guest_capacity > 0) ? esc_html($available) : 'n/a'; ?></td>
                        <td class="collapse column-small center"><?php echo ($event->max_guests_per_registration > 0) ? esc_html($event->max_guests_per_registration) : 'n/a'; ?></td>
                        <td class="collapse"><?php echo explode('@', $event->admin_email)[0];?></td>
                        <td class="right">
                            <button class="button">
                                <a href="<?php echo admin_url('admin.php?page=event-admin-new&action=edit&id=' . $event->id); ?>">Edit</a>
                            </button>
                            <button class="button copy-url" data-url="<?php echo esc_url($registration_url); ?>">
                                Link
                            </button>
                            <button class="button delete-event" 
                                data-event-id="<?php echo esc_attr($event->id); ?>" 
                                data-nonce="<?php echo wp_create_nonce('delete_event'); ?>">
                                Delete
                                </a>
                            </buttom>
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
</div>

<!-- Event Details Modal -->
<div id="event-details-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close-modal">&times;</span>
            <h2>Copy Previous Event</h2>
        </div>
        <div class="modal-body">
            <div id="event-details"></div>
        </div>
    </div>
</div>

<div id="copy-event-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close-modal">&times;</span>
            <h2>Copy Event</h2>
        </div>
        <div class="modal-body">
            <label for="previous-events-dropdown">Select a Previous Event:</label>
            <select id="previous-events-dropdown">
                <option value="none">-- Select an Event --</option>
                <?php
                global $wpdb;
                $previous_events = $wpdb->get_results("
                    SELECT DISTINCT event_name 
                    FROM {$wpdb->prefix}sct_events 
                    ORDER BY event_name ASC
                ");
                foreach ($previous_events as $event) {
                    echo '<option value="' . esc_attr($event->event_name) . '">' . esc_html($event->event_name) . '</option>';
                }
                ?>
            </select>
            <br><br>
            <label for="new-event-date">New Event Date:</label>
            <input type="date" id="new-event-date">
        </div>
        <div class="modal-footer">
            <button id="confirm-copy-event" class="button button-primary">Copy Event</button>
            <button class="button close-modal">Cancel</button>
        </div>
    </div>
</div>
