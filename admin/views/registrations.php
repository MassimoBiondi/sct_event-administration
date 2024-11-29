<div class="wrap">
    <h1>Registration Management</h1>
    <?php wp_nonce_field('update_registration_nonce', 'update_registration_security'); ?>
    <?php foreach ($upcoming_events as $event): 
        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d ORDER BY registration_date DESC",
            $event->id
        ));
        
        // Get total capacity and registered guests
        $total_registered = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $total_registered = $total_registered ?: 0;
        $remaining_capacity = $event->guest_capacity - $total_registered;
    ?>
        <div class="event-registrations">
            <div class="event-header">
                <h2><?php echo esc_html($event->event_name); ?> - <?php echo date('Y-m-d', strtotime($event->event_date)); ?></h2>
                <button class="button button-small mail-to-all" 
                        data-event-id="<?php echo esc_attr($event->id); ?>"
                        data-event-name="<?php echo esc_attr($event->event_name); ?>">
                        <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                    Mail to All
                </button>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 10px;">
                    <input type="hidden" name="action" value="export_event_registrations" />
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>" />
                    <?php wp_nonce_field('export_event_registrations_' . $event->id); ?>
                    <button type="submit" class="button button-small">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                        Export CSV
                    </button>
                </form>
            </div>            
            <!-- <p>
                Total Capacity: <?php echo esc_html($event->guest_capacity); ?> |
                Registered Guests: <?php echo esc_html($total_registered); ?> |
                Remaining Capacity: <?php echo esc_html($remaining_capacity); ?>
            </p> -->
            
            <?php if (!empty($registrations)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Guests</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <tr data-registration-id="<?php echo $registration->id; ?>">
                                <td><?php echo esc_html($registration->name); ?></td>
                                <td><?php echo esc_html($registration->email); ?></td>
                                <td class="guest-count-cell">
                                    <input type="number" 
                                           class="guest-count" 
                                           value="<?php echo esc_attr($registration->guest_count); ?>"
                                           min="1"
                                           data-original="<?php echo esc_attr($registration->guest_count); ?>">
                                    <button class="button button-small update-guest-count" style="display: none;">
                                        Update
                                    </button>
                                </td>
                                <td><?php echo esc_html($registration->registration_date); ?></td>
                                <td>
                                    <button class="button button-small send-email" 
                                            data-email="<?php echo esc_attr($registration->email); ?>">
                                            <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                                        Send Email
                                    </button>
                                    <button class="button button-small delete-registration" 
                                            data-registration-id="<?php echo $registration->id; ?>"
                                            data-nonce="<?php echo wp_create_nonce('delete_registration'); ?>">
                                            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 5px;"></span>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th></th>
                            <th>Registered Guests: <?php echo esc_html($total_registered); ?></th>
                            <th>Total Capacity: <?php echo esc_html($event->guest_capacity); ?></th>
                            <th>Remaining Capacity: <?php echo esc_html($remaining_capacity); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p>No registrations for this event.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <!-- Add this at the bottom of the file, before the closing </div> -->
    <div id="email-modal" class="email-modal">
        <div class="email-modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2>Send Email</h2>
            </div>
            
            <form id="send-email-form">
                <?php wp_nonce_field('send_email_nonce', 'email_security'); ?>
                <input type="hidden" name="action" value="send_registration_email">
                <input type="hidden" name="recipient_email" id="recipient_email">
                <input type="hidden" name="registration_id" id="registration_id">
                <input type="hidden" name="event_id" id="event_id">
                <input type="hidden" name="is_mass_email" id="is_mass_email" value="0">
                
                <div class="form-field">
                    <label for="email_subject">Subject:</label>
                    <input type="text" id="email_subject" name="email_subject" class="regular-text" required>
                </div>
                
                <div class="form-field">
                    <label for="email_body">Message:</label>
                    <textarea id="email_body" name="email_body" rows="10" class="large-text" required></textarea>
                </div>

                <div class="form-field placeholder-info">
                    <p><strong>Available placeholders:</strong></p>
                    <p>
                        <code>{name}</code> - Registrant's name<br>
                        <code>{event_name}</code> - Event name<br>
                        <code>{event_date}</code> - Event date<br>
                        <code>{event_time}</code> - Event time<br>
                        <code>{guest_count}</code> - Number of guests<br>
                        <code>{location}</code> - Event location
                    </p>
                </div>
                
                <div class="submit-buttons">
                    <button type="button" class="button cancel-email">Cancel</button>
                    <button type="submit" class="button button-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>


</div>
