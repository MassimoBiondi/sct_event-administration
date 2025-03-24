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
                    <span class="dashicons dashicons-admin-users"></span>
                <?php endif; ?>
                <?php if ($event->by_lottery): ?>
                    <span class="dashicons dashicons-tickets-alt"></span>
                <?php endif; ?>
                <h2><?php echo esc_html($event->event_name); ?></h2>
                <span class="event-date"><?php echo date('Y-m-d', strtotime($event->event_date)); ?></span>

                <button class="button button-small mail-to-all" 
                        data-event-id="<?php echo esc_attr($event->id); ?>"
                        data-event-name="<?php echo esc_attr($event->event_name); ?>">
                        <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                </button>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 15px;">
                    <input type="hidden" name="action" value="export_event_registrations" />
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>" />
                    <?php wp_nonce_field('export_event_registrations_' . $event->id); ?>
                    <button type="submit" class="button button-small">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                    </button>
                </form>
                <button class="button button-small add-registration" style="display: inline-block; margin-left: 15px;" data-event-id="<?php echo esc_attr($event->id); ?>" data-children-counted-separately="<?php echo esc_attr($event->children_counted_separately); ?>" data-member-only="<?php echo esc_attr($event->member_only); ?>" data-member-price="<?php echo esc_attr($event->member_price); ?>" data-non-member-price="<?php echo esc_attr($event->non_member_price); ?>">
                    <span class="dashicons dashicons-plus" style="vertical-align: middle; margin-right: 5px;"></span>
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
                <table class="wp-list-table widefat fixed striped" data-event-capacity="<?php echo esc_attr($event->guest_capacity); ?>" data-total-registered="<?php echo esc_attr($total_registered); ?>" data-total-guests="<?php echo esc_attr($total_guests); ?>" data-total-members="<?php echo esc_attr($total_member_guests); ?>">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <?php if ($event->member_only): ?>
                                <th>Members</th>
                            <?php elseif ($event->non_member_price > 0 && $event->member_price == 0): ?>
                                <th>Guests</th>
                            <?php else: ?>
                                <?php if ($event->non_member_price > 0 && $event->member_price > 0): ?>
                                    <th>Members</th>
                                    <th>Guests</th>
                                <?php else: ?>
                                    <th>Guests</th>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($event->children_counted_separately): ?>
                                <th>Children</th>
                            <?php endif; ?>
                            <?php if ($event->member_price > 0 || $event->non_member_price > 0): ?>
                                <th>Total Price</th>
                            <?php endif; ?>
                            <th>Registration Date</th>
                            <th class="right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): 
                            $total_price = ($event->member_price * $registration->member_guests) + ($event->non_member_price * $registration->non_member_guests);
                        ?>
                            <tr <?php echo $registration->is_winner ? 'class="winner"' : ''; ?> data-registration-id="<?php echo $registration->id; ?>" data-member-price="<?php echo esc_attr($event->member_price); ?>" data-non-member-price="<?php echo esc_attr($event->non_member_price); ?>">
                                <td>
                                    <?php echo $registration->is_winner ? '<span class="dashicons dashicons-yes"></span>' : ''; ?>
                                    <?php echo esc_html($registration->name); ?>
                                </td>
                                <td><?php echo esc_html($registration->email); ?></td>
                                <?php if ($event->member_only): ?>
                                    <td class="member-guest-count-cell">
                                        <input type="number" 
                                               class="member-guest-count small-text" 
                                               value="<?php echo esc_attr($registration->member_guests); ?>"
                                               min="0"
                                               data-original="<?php echo esc_attr($registration->member_guests); ?>">
                                        <button class="button button-small update-member-guest-count" style="display: none;">
                                            Update
                                        </button>
                                    </td>
                                <?php elseif ($event->non_member_price > 0 && $event->member_price == 0): ?>
                                    <td class="non-member-guest-count-cell">
                                        <input type="number" 
                                               class="non-member-guest-count small-text" 
                                               value="<?php echo esc_attr($registration->non_member_guests); ?>"
                                               min="0"
                                               data-original="<?php echo esc_attr($registration->non_member_guests); ?>">
                                        <button class="button button-small update-non-member-guest-count" style="display: none;">
                                            Update
                                        </button>
                                    </td>
                                <?php else: ?>
                                    <?php if ($event->member_price != 0 ): ?>
                                        <td class="member-guest-count-cell">
                                            <input type="number" 
                                                class="member-guest-count small-text" 
                                                value="<?php echo esc_attr($registration->member_guests); ?>"
                                                min="0"
                                                data-original="<?php echo esc_attr($registration->member_guests); ?>">
                                            <button class="button button-small update-member-guest-count" style="display: none;">
                                                Update
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                    <td class="non-member-guest-count-cell">
                                        <input type="number" 
                                               class="non-member-guest-count small-text" 
                                               value="<?php echo esc_attr($registration->non_member_guests); ?>"
                                               min="0"
                                               data-original="<?php echo esc_attr($registration->non_member_guests); ?>">
                                        <button class="button button-small update-non-member-guest-count" style="display: none;">
                                            Update
                                        </button>
                                    </td>
                                <?php endif; ?>
                                <?php if ($event->children_counted_separately): ?>
                                    <td class="children-guest-count-cell">
                                        <input type="number" 
                                               class="children-guest-count small-text" 
                                               value="<?php echo esc_attr($registration->children_guests); ?>"
                                               min="0"
                                               data-original="<?php echo esc_attr($registration->children_guests); ?>">
                                        <button class="button button-small update-children-guest-count" style="display: none;">
                                            Update
                                        </button>
                                    </td>
                                <?php endif; ?>
                                <?php if ($event->member_price > 0 || $event->non_member_price > 0): ?>
                                    <td class="total-price-cell">
                                        <?php echo $sct_settings['currency_symbol']; ?>
                                        <span class="total-price"><?php echo esc_html(number_format($total_price, $sct_settings['currency_format'])); ?></span>
                                    </td>
                                <?php endif; ?>
                                <td><?php echo esc_html($registration->registration_date); ?></td>
                                <td class="right">
                                    <a class="send-email" 
                                            data-email="<?php echo esc_attr($registration->email); ?>">
                                            <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                                    </a>
                                    <span class="trash">
                                        <a class="delete-registration" 
                                                data-registration-id="<?php echo $registration->id; ?>"
                                                data-nonce="<?php echo wp_create_nonce('delete_registration'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th></th>
                            <?php if ($event->member_only): ?>
                                <th>Members: <span class="total-guests"><?php echo esc_html($total_member_guests); ?></span></th>
                                <?php if ($event->member_price > 0 || $event->non_member_price > 0): ?>
                                    <th id="table-total-price" class="table-total-price"><?php echo $sct_settings['currency_symbol']; ?> <span class="total-price-value">0.00</span></th>
                                <?php endif; ?>
                                <th></th>
                            <?php elseif ($event->non_member_price > 0 && $event->member_price == 0): ?>
                                <th>Guests: <span class="total-non-members"><?php echo esc_html($total_non_member_guests); ?></span></th>
                                <?php if ($event->member_price > 0 || $event->non_member_price > 0): ?>
                                    <th id="table-total-price" class="table-total-price"><?php echo $sct_settings['currency_symbol']; ?> <span class="total-price-value">0.00</span></th>
                                <?php endif; ?>
                                <th>Total Guests: <span class="total-guests"><?php echo esc_html($total_non_member_guests); ?></span></th>
                                <th class="right"><span class="remaining-capacity"></span></th>
                            <?php elseif (!$event->member_only && $event->member_price == 0): ?>
                                <th>Guests: <span class="total-non-members"><?php echo esc_html($total_non_member_guests); ?></span></th>
                                <?php if ($event->children_counted_separately): ?>
                                    <th>Children: <span class="total-children"><?php echo esc_html($total_children_guests); ?></span></th>
                                <?php else: ?>
                                    <th></th>
                                <?php endif; ?>
                            <?php else: ?>
                                <th>Members: <span class="total-members"><?php echo esc_html($total_member_guests); ?></span></th>
                                <th>Guests: <span class="total-non-members"><?php echo esc_html($total_non_member_guests); ?></span></th>
                                <?php if ($event->children_counted_separately): ?>
                                    <th>Children: <span class="total-children"><?php echo esc_html($total_children_guests); ?></span></th>
                                <?php endif; ?>
                                <th id="table-total-price" class="table-total-price"><?php echo $sct_settings['currency_symbol']; ?> <span class="total-price-value">0.00</span></th>
                            <?php endif; ?>
                            <?php
                                if ($non_null_count >= 2) {
                                    echo '<th>Total Guests: <span class="total-guests">' . esc_html($total_member_guests + $total_non_member_guests + $total_children_guests). '</span></th>';
                                }
                            ?>
                            <th class="right"><span class="remaining-capacity"></span></th>
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
                        <code>{location_name}</code> - Event location name<br>
                        <code>{description}</code> - Event description<br>
                        <code>{location_link}</code> - Event location link<br>
                        <code>{guest_capacity}</code> - Event guest capacity<br>
                        <!-- <code>{max_guests_per_registration}</code> - Max guests per registration<br> -->
                        <!-- <code>{admin_email}</code> - Admin email<br> -->
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
        <div class="email-modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2>Add Registration</h2>
            </div>
            
            <form id="add-registration-form">
                <?php wp_nonce_field('add_registration_nonce', 'add_registration_security'); ?>
                <input type="hidden" name="action" value="add_registration">
                <input type="hidden" name="event_id" id="add_registration_event_id">
                
                <div class="form-field">
                    <label for="registration_name">Name:</label>
                    <input type="text" id="registration_name" name="name" class="regular-text" required>
                </div>
                
                <div class="form-field">
                    <label for="registration_email">Email:</label>
                    <input type="email" id="registration_email" name="email" class="regular-text" required>
                </div>
                
                <div class="form-field" id="registration_member_guests_field">
                    <label for="registration_member_guests">Members:</label>
                    <input type="number" id="registration_member_guests" name="member_guests" class="small-text" min="0" value="0">
                </div>
                
                <div class="form-field" id="registration_non_member_guests_field">
                    <label for="registration_non_member_guests">Guests:</label>
                    <input type="number" id="registration_non_member_guests" name="non_member_guests" class="small-text" min="0" value="0">
                </div>
                
                <div class="form-field" id="registration_children_guests_field">
                    <label for="registration_children_guests">Children:</label>
                    <input type="number" id="registration_children_guests" name="children_guests" class="small-text" min="0" value="0">
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
