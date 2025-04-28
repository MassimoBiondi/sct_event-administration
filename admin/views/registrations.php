<div class="wrap">
    <h1>Registration Management</h1>
    <?php wp_nonce_field('delete_registration_nonce', 'delete_registration_security'); ?>
    <?php $sct_settings = get_option('event_admin_settings', []); ?>
    <div id="currency-format" data-currency-format="<?php echo esc_attr($sct_settings['currency_format']); ?>" style="display: none;"></div>
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
        $remaining_capacity = ($event->guest_capacity > 0) ? $event->guest_capacity - $total_registered : 'n/a';

        // Get total member and non-member guests
        $total_member_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(member_guests) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $total_member_guests = $total_member_guests ?: 0;

        $total_non_member_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(non_member_guests) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $total_non_member_guests = $total_non_member_guests ?: 0;

        $total_children_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(children_guests) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $total_children_guests = $total_children_guests ?: 0;

        $non_null_count = 0;
        // Check each field and increment the count if it's not null
        if ($total_member_guests > 0) {
            $non_null_count++;
        }
        if ($total_non_member_guests > 0) {
            $non_null_count++;
        }
        if ($total_children_guests > 0) {
            $non_null_count++;
        }
        $total_guests = $total_member_guests + $total_non_member_guests + $total_children_guests;
    ?>
        <div class="event-registrations">
            <div class="event-header">
                <?php if ($event->member_only): ?>
                    <img src="<?php echo plugin_dir_url(__DIR__) . 'css/img/member.svg'; ?>" alt="Lottery" class="lottery-icon" style="width: 20px; height: 20px;">
                <?php endif; ?>
                <?php if ($event->by_lottery): ?>
                    <img src="<?php echo plugin_dir_url(__DIR__) . 'css/img/lottery_wheel.svg'; ?>" alt="Lottery" class="lottery-icon" style="width: 20px; height: 20px;">
                <?php endif; ?>
                <h2><?php echo esc_html($event->event_name); ?></h2>
                <span class="event-date"><?php echo date('Y-m-d', strtotime($event->event_date)); ?></span>

                <button class="button button-small mail-to-all" 
                        data-event-id="<?php echo esc_attr($event->id); ?>"
                        data-event-name="<?php echo esc_attr($event->event_name); ?>">
                        <!-- <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span> -->
                        Email to All
                </button>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 15px;">
                    <input type="hidden" name="action" value="export_event_registrations" />
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>" />
                    <?php wp_nonce_field('export_event_registrations_' . $event->id); ?>
                    <button type="submit" class="button button-small">
                        <!-- <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span> -->
                        Export CSV
                    </button>
                </form>
                <button class="button button-small add-registration" 
                        data-event-id="<?php echo esc_attr($event->id); ?>"
                        data-pricing-options="<?php echo esc_attr(json_encode(maybe_unserialize($event->pricing_options))); ?>">
                    <!-- <span class="dashicons dashicons-plus" style="vertical-align: middle; margin-right: 5px;"></span> -->
                    Add Registration
                </button>
                <?php 
                // Check if winners have already been selected
                $winners_selected = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d AND is_winner = 1",
                    $event->id
                )) > 0;
                ?>
                <?php if ($event->by_lottery): ?>
                    <button class="button button-small select-winners" style="display: inline-block; margin-left: 15px;" data-event-id="<?php echo esc_attr($event->id); ?>" <?php echo $winners_selected ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-awards" style="vertical-align: middle; margin-right: 5px;"></span>
                        Select Winners
                    </button>
                <?php endif; ?>
            </div>            
            
            <?php if (!empty($registrations)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <?php if (!empty($event->pricing_options)) : ?>
                                <?php
                                $pricing_options = maybe_unserialize($event->pricing_options);
                                foreach ($pricing_options as $option) : ?>
                                    <th class="collapse column-small center"><?php echo esc_html($option['name']); ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <!-- <th>Registration Date</th> -->
                            <th class="collapse column-small center">Total Guests</th>
                            <th class="right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration) : 
                            $guest_details = maybe_unserialize($registration->guest_details);
                            $total_guests = $registration->guest_count;
                        ?>
                            <tr data-registration-id="<?php echo esc_attr($registration->id); ?>">
                                <td><?php echo esc_html($registration->name); ?></td>
                                <td class="collapse"><?php echo esc_html($registration->email); ?></td>
                                <?php if (!empty($event->pricing_options)) : ?>
                                    <?php foreach ($pricing_options as $index => $option) : 
                                        $guest_count = isset($guest_details[$index]) ? intval($guest_details[$index]) : 0;
                                    ?>
                                        <td class="collapse column-small center">
                                            <input type="number" 
                                                   class="pricing-option-guest-count small-text" 
                                                   data-pricing-index="<?php echo esc_attr($index); ?>" 
                                                   value="<?php echo esc_attr($guest_count); ?>"
                                                   data-original="<?php echo esc_attr($guest_count); ?>" 
                                                   min="0">
                                        </td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <td class="total-guests collapse column-small center">
                                    <?php if (empty($event->pricing_options)) : ?>
                                        <input type="number" 
                                               class="guest-count small-text" 
                                               value="<?php echo esc_attr($total_guests); ?>"
                                               data-original="<?php echo esc_attr($total_guests); ?>" 
                                               min="0">
                                    <?php else : ?>
                                        <?php echo esc_attr($total_guests); ?>
                                    <?php endif; ?>
                                </td>
                                <!-- <td><?php echo esc_html(date('Y-m-d H:i', strtotime($registration->registration_date))); ?></td> -->
                                <td class="right">
                                    <button class="button update-guest-counts" style="display: none;" data-registration-id="<?php echo esc_attr($registration->id); ?>">
                                        Update
                                    </button>
                                    <button class="button send-email" 
                                            data-registration-id="<?php echo esc_attr($registration->id); ?>" 
                                            data-recipient-email="<?php echo esc_attr($registration->email); ?>" 
                                            data-event-name="<?php echo esc_attr($event->event_name); ?>">
                                        Email
                                    </button>
                                    <button class="button delete-registration" 
                                            data-registration-id="<?php echo esc_attr($registration->id); ?>" 
                                            data-nonce="<?php echo wp_create_nonce('delete_registration'); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
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
                <h2>Send Email to <span id="admin_mail_recipient" class="status-indicator status-failed" style="font-size: 1.5em;" ></span></h2>
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
                        <!-- <code>{event_id}</code> - Event ID<br> -->
                        <code>{name}</code> - Registrant's name<br>
                        <code>{email}</code> - Registrant's email<br>
                        <code>{guest_count}</code> - Number of guests<br>
                        <code>{member_guests}</code> - Number of member guests<br>
                        <code>{non_member_guests}</code> - Number of non-member guests<br>
                        <!-- <code>{registration_date}</code> - Registration date<br> -->
                        <code>{event_name}</code> - Event name<br>
                        <code>{event_date}</code> - Event date<br>
                        <code>{event_time}</code> - Event time<br>
                        <code>{description}</code> - Event description<br>
                        <code>{location_name}</code> - Event location name<br>
                        <code>{location_link}</code> - Event location link<br>
                        <code>{location_url}</code> - Event location url<br>
                        <code>{guest_capacity}</code> - Event guest capacity<br>
                        <code>{member_price}</code> - Member price<br>
                        <code>{non_member_price}</code> - Non-member price<br>
                        <code>{member_only}</code> - Member only event<br>
                        <code>{total_price}</code> - Total price
                        <code>{remaining_capacity}</code> - Remaining capacity<br>
                         <code>{children_counted_separately}</code> - Children counted separately<br>
                        <code>{children_guests}</code> - Number of children guests<br>
                    </p>
                </div>
                
                <div class="submit-buttons">
                    <button type="button" class="button cancel-email">Cancel</button>
                    <button type="submit" class="button button-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Registration Modal -->
    <div id="add-registration-modal" class="email-modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2>Add Registration</h2>
            </div>
            <form id="add-registration-form">
                <?php wp_nonce_field('add_registration_nonce', 'security'); ?>
                <input type="hidden" name="action" value="add_registration">
                <input type="hidden" name="event_id" id="add_registration_event_id">

                <div class="form-field">
                    <label for="add_registration_name">Name:</label>
                    <input type="text" id="add_registration_name" name="name" required>
                </div>

                <div class="form-field">
                    <label for="add_registration_email">Email:</label>
                    <input type="email" id="add_registration_email" name="email" required>
                </div>

                <div id="add_registration_guest_count_field" class="form-field">
                    <label for="add_registration_guest_count">Guest Count:</label>
                    <input type="number" id="add_registration_guest_count" name="guest_count" min="0" value="0">
                </div>

                <div id="add_registration_pricing_options_container" class="form-field">
                    <!-- Dynamic pricing options will be appended here -->
                </div>

                <div class="submit-buttons">
                    <button type="button" class="button cancel-registration">Cancel</button>
                    <button type="submit" class="button button-primary">Add Registration</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Winners Selection Modal -->
    <div id="select-winners-modal" class="email-modal">
        <div class="email-modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2>Select Winners</h2>
            </div>
            
            <form id="select-winners-form">
                <?php wp_nonce_field('select_random_winners_nonce', 'security'); ?>
                <input type="hidden" name="action" value="select_random_winners">
                <input type="hidden" name="event_id" id="select_winners_event_id">
                
                <div class="form-field">
                    <label for="num_winners">Number of Winners:</label>
                    <input type="number" id="num_winners" name="num_winners" min="1" required>
                </div>
                
                <div class="submit-buttons">
                    <button type="button" class="button cancel-selection">Cancel</button>
                    <button type="submit" class="button button-primary">Select Winners</button>
                </div>
            </form>
        </div>
    </div>
</div>
