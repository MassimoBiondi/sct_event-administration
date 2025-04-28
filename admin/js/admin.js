jQuery(document).ready(function($) {
    // Function to display admin notices (already present)
    function showAdminNotice(message, type) {
        // Ensure type is one of the allowed values, default to 'info'
        const allowedTypes = ['success', 'error', 'warning', 'info'];
        type = allowedTypes.includes(type) ? type : 'info';

        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p>');
        // Attempt to insert after the header, fallback to top of wrap if header isn't found
        var insertPoint = $('.wp-header-end');
        if (insertPoint.length === 0) {
            insertPoint = $('#wpbody-content .wrap').first(); // Common container
            if (insertPoint.length > 0) {
                insertPoint.prepend(notice);
            } else {
                // Fallback if no suitable container found (less ideal)
                $('body').prepend(notice);
            }
        } else {
             insertPoint.after(notice);
        }

        // Make the notice dismissible via WordPress's standard script
        if (typeof notice !== 'undefined' && notice.length > 0 && typeof notice.on === 'function') {
            notice.on('click', '.notice-dismiss', function() {
                $(this).closest('.notice').fadeOut(function() {
                    $(this).remove();
                });
            });
        }

        // Optional: Auto-fade out after 5 seconds
        setTimeout(function() {
            if (notice.closest('body').length > 0) { // Check if notice still exists
                 notice.fadeOut(function() {
                    $(this).remove();
                });
            }
        }, 5000); // 5 seconds
    }

    // Show the modal when the "Copy Event" button is clicked
    $('#copy-event-button').on('click', function () {
        $('#copy-event-modal').show();
        $('#copy-event-modal').css('display', 'block'); // Ensure the modal is displayed properly
    });

    // Close the modal when the close button or cancel button is clicked
    $(document).on('click', '.close-modal', function () {
        $(this).closest('.modal').hide();
        clearModalFields(); // Clear the modal fields when closed
    });

    $(document).on('change', '#previous-events-dropdown', function () {
        console.log('Selected value:', $(this).val()); // Debugging to check the selected value
        selectedEventId = $(this).val();
        // $(this).trigger('change'); // Ensure the value is updated
    });

    // Handle the "Copy Event" confirmation
    $(document).on('click', '#confirm-copy-event', function () {
        // Ensure the dropdown exists and retrieve its value
        selectedEventName = $('#previous-events-dropdown').val();
        newEventDate = $('#new-event-date').val();

        // Debugging: Log the values to ensure they are being retrieved
        console.log('Selected Event Name:', selectedEventName);
        console.log('New Event Date:', newEventDate);

        // Validate the fields
        if (!selectedEventName || !newEventDate) {
            // --- REPLACED alert ---
            showAdminNotice('Please select an event and provide a new date.', 'warning');
            return;
        }

        // Send AJAX request to copy the event
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'copy_event_by_name',
                event_name: selectedEventName,
                new_event_date: newEventDate,
                security: eventAdmin.copy_event_nonce
            },
            success: function (response) {
                if (response.success) {
                    // --- REPLACED alert ---
                    showAdminNotice('Event copied successfully.', 'success');
                    location.reload();
                } else {
                    // --- REPLACED alert ---
                    showAdminNotice(response.data.message || 'Failed to copy event.', 'error');
                }
            },
            error: function () {
                // --- REPLACED alert ---
                showAdminNotice('An error occurred while copying the event.', 'error');
            }
        });
    });

    // Close the modal when clicking outside the modal content
    $(window).on('click', function (e) {
        if ($(e.target).hasClass('modal')) {
            $(e.target).hide();
            clearModalFields(); // Clear the modal fields when closed
        }
    });

    // Function to clear the modal fields
    function clearModalFields() {
        $('#previous-events-dropdown').val('');
        $('#new-event-date').val('');
    }

    // --- Commented out block with alerts remains commented ---
    // $('#copy-selected-event').on('click', function () {
    //     const selectedEventName = $('#previous-events-dropdown').val();
    //     const newEventDate = prompt('Enter the new date for the copied event (YYYY-MM-DD):');
    //
    //     if (!selectedEventName || !newEventDate) {
    //         alert('Please select an event and provide a new date.');
    //         return;
    //     }
    //
    //     // Send AJAX request to copy the event
    //     $.ajax({
    //         url: ajaxurl,
    //         type: 'POST',
    //         data: {
    //             action: 'copy_event_by_name',
    //             event_name: selectedEventName,
    //             new_event_date: newEventDate,
    //             security: eventAdmin.copy_event_nonce
    //         },
    //         success: function (response) {
    //             if (response.success) {
    //                 alert('Event copied successfully.');
    //                 location.reload();
    //             } else {
    //                 alert(response.data.message || 'Failed to copy event.');
    //             }
    //         },
    //         error: function () {
    //             alert('An error occurred while copying the event.');
    //         }
    //     });
    // });
    // --- End commented block ---


    let mediaUploader;

    $('#upload-thumbnail-button').on('click', function (e) {
        e.preventDefault();
        console.log('Button clicked'); // Debugging

        // If the uploader object has already been created, reopen it.
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create a new media uploader instance.
        mediaUploader = wp.media({
            title: 'Select Event Thumbnail',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#thumbnail_url').val(attachment.url);
            $('#thumbnail-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">');
        });

        // Open the uploader dialog.
        mediaUploader.open();
    });

    // Handle event form submission
    $('#add-event-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    // Redirect on success, maybe show notice on the next page via PHP?
                    // showAdminNotice('Event added successfully.', 'success'); // Notice might not be seen before redirect
                    window.location.href = 'admin.php?page=event-admin&event_added=1'; // Add query arg for PHP notice
                } else {
                    // --- REPLACED alert ---
                    showAdminNotice('Error: ' + (response.data.message || 'Could not add event.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                 showAdminNotice('An error occurred: ' + error, 'error');
            }
        });
    });

    // Handle event deletion
    $('.delete-event').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this event? This will also delete all associated registrations.')) {
            return;
        }

        var button = $(this);
        var eventId = button.data('event-id');
        var nonce = button.data('nonce');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_event',
                event_id: eventId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Event deleted successfully', 'success');
                    // Use DataTables API to remove row if available, otherwise fallback
                    var dtTable = button.closest('.wp-list-table').DataTable();
                    if ($.fn.DataTable.isDataTable(button.closest('.wp-list-table'))) {
                         dtTable.row(button.closest('tr')).remove().draw();
                    } else {
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                    }
                } else {
                    showAdminNotice(response.data.message || 'Error deleting event', 'error');
                }
            },
            error: function() {
                showAdminNotice('Error deleting event', 'error');
            }
        });
    });

    // Handle copy URL button click
    $('.copy-url').on('click', function() {
        var button = $(this);
        var url = button.data('url');

        // Create a temporary input element to hold the URL
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(url).select();
        var success = false;
        try {
            success = document.execCommand('copy');
        } catch (err) {
            console.error('Copy command failed:', err);
        }
        tempInput.remove();

        if (success) {
            showAdminNotice('URL <strong>'+ url +'</strong> copied to clipboard.', 'success');
        } else {
             showAdminNotice('Failed to copy URL. Please copy it manually.', 'warning');
             // Optionally select the text for manual copy
             window.prompt("Copy to clipboard: Ctrl+C, Enter", url);
        }
    });

    $(document).on('click', '.copy-previous-event', function (e) {
        e.preventDefault();

        const eventId = $(this).data('event-id');
        const nonce = $(this).data('nonce');

        // Fetch previous events with the same name
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_previous_events',
                event_id: eventId,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    // Display the modal with the list of previous events
                    const modalContent = $('#event-details-modal .modal-body #event-details');
                    modalContent.html(response.data.html);
                    $('#event-details-modal').show();
                } else {
                    // --- REPLACED alert ---
                    showAdminNotice(response.data.message || 'Error fetching previous events.', 'error');
                }
            },
            error: function () {
                // --- REPLACED alert ---
                showAdminNotice('Error fetching previous events.', 'error');
            }
        });
    });

    // Handle view event details button click
    $(document).on('click', '.event-details', function(e) {
        e.preventDefault();
        var event = $(this).data('event');
        var modal = $('#event-details-modal');
        var details = `
            <p><strong>Name:</strong> ${event.event_name}</p>
            <p><strong>Date:</strong> ${event.event_date}</p>
            <p><strong>Time:</strong> ${event.event_time}</p>
            <p><strong>Location:</strong> ${event.location_name}</p>
            <p><strong>Location Link:</strong> <a href="${event.location_link}" target="_blank">${event.location_link}</a></p>
            <p><strong>Description:</strong> ${event.description}</p>
            <p><strong>Capacity:</strong> ${event.guest_capacity}</p>
            <p><strong>Registered:</strong> ${event.registered}</p>
            <p><strong>Available:</strong> ${event.available}</p>
            <p><strong>Max Guests per Registration:</strong> ${event.max_guests_per_registration}</p>
            <p><strong>Organizer:</strong> ${event.admin_email}</p>
            <p><strong>Member Only:</strong> ${event.member_only ? 'Yes' : 'No'}</p>
            <p><strong>Member Price:</strong> ${event.member_price}</p>
            <p><strong>Non-Member Price:</strong> ${event.non_member_price}</p>
            <p><strong>Children Counted Separately:</strong> ${event.children_counted_separately == 1 ? 'Yes' : 'No'}</p>
        `;
        $('#event-details').html(details);
        modal.show();
    });



    // Add click handlers for the placeholder codes
    $('.placeholder-info code').each(function() {
        $(this)
            .css('cursor', 'pointer')
            .attr('title', 'Click to insert')
            .on('click', function() {
                var placeholder = $(this).text() + ' ';
                var $emailBody = $('#email_body');
                var $customEmailTemplate = $('#custom_email_template');

                // Function to insert placeholder at cursor position
                function insertPlaceholder($textarea) {
                    var startPos = $textarea[0].selectionStart;
                    var endPos = $textarea[0].selectionEnd;
                    var currentContent = $textarea.val();
                    var newContent = currentContent.substring(0, startPos) +
                                     placeholder +
                                     currentContent.substring(endPos);
                    $textarea.val(newContent);
                    var newCursorPos = startPos + placeholder.length;
                    $textarea[0].setSelectionRange(newCursorPos, newCursorPos);
                    $textarea.focus();
                }

                // Insert placeholder into the focused textarea
                if ($emailBody.is(':focus') || $emailBody.length && !$customEmailTemplate.length) {
                    insertPlaceholder($emailBody);
                } else if ($customEmailTemplate.is(':focus') || $customEmailTemplate.length) {
                    insertPlaceholder($customEmailTemplate);
                } else if ($emailBody.length) { // Fallback if neither is focused
                     insertPlaceholder($emailBody);
                } else if ($customEmailTemplate.length) {
                     insertPlaceholder($customEmailTemplate);
                }
            });
    });


    $('.guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var row = input.closest('tr'); // Find the closest <tr> for the input
        var updateButton = row.find('.update-guest-counts'); // Find the button within the same <tr>

        console.log('Update button found:', updateButton.length > 0);
        console.log('Original value:', originalValue, 'Current value:', input.val());

        // Show or hide the update button based on whether the value has changed
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    $('.pricing-option-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var row = input.closest('tr'); // Find the closest <tr> for the input
        var updateButton = row.find('.update-guest-counts'); // Find the button within the same <tr>

        console.log('Update button found:', updateButton.length > 0);
        console.log('Original value:', originalValue, 'Current value:', input.val());

        // Show or hide the update button based on whether the value has changed
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    // Calculate total price dynamically
    $(document).on('input', '.guest-count', function () {
        let totalPrice = 0;

        $('.guest-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).siblings('input[name$="[price]"]').val()) || 0;
            totalPrice += count * price;
        });

        $('#total_price').val(totalPrice.toFixed(2));
    });

    // Handle member guest count updates
    $('.member-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var updateButton = input.siblings('.update-member-guest-count');

        // Reset all other member guest count inputs except this one
        // $('.member-guest-count').not(this).each(function() {
        //     var otherInput = $(this);
        //     otherInput.val(otherInput.data('original'));
        //     otherInput.siblings('.update-member-guest-count').hide();
        // });

        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    // Handle non-member guest count updates
    $('.non-member-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var updateButton = input.siblings('.update-non-member-guest-count');

        // Reset all other non-member guest count inputs except this one
        // $('.non-member-guest-count').not(this).each(function() {
        //     var otherInput = $(this);
        //     otherInput.val(otherInput.data('original'));
        //     otherInput.siblings('.update-non-member-guest-count').hide();
        // });

        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    // Handle registration deletion
    $(document).on('click', '.delete-registration', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this registration?')) {
            return;
        }

        const button = $(this);
        const registrationId = button.data('registration-id');
        const nonce = button.data('nonce');

        // Send AJAX request to delete the registration
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_registration',
                registration_id: registrationId,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    // --- REPLACED alert ---
                    showAdminNotice('Registration deleted successfully.', 'success');
                    // Remove the row from the table using DataTables API if available
                    var dtTable = button.closest('.wp-list-table').DataTable();
                     if ($.fn.DataTable.isDataTable(button.closest('.wp-list-table'))) {
                         dtTable.row(button.closest('tr')).remove().draw();
                    } else {
                        button.closest('tr').fadeOut(400, function () {
                            $(this).remove();
                        });
                    }
                    // Recalculate totals after deletion
                    calculateTotals(button.closest('.wp-list-table'));
                } else {
                    // --- REPLACED alert ---
                    showAdminNotice(response.data.message || 'Error deleting registration.', 'error');
                }
            },
            error: function () {
                // --- REPLACED alert ---
                showAdminNotice('Error deleting registration.', 'error');
            }
        });
    });

     // Email modal functionality
     var modal = $('#email-modal');

     // Open modal when send email button is clicked
     $('.send-email').on('click', function(e) {
         e.preventDefault();
         var button = $(this);
         var row = button.closest('tr');
         var registrationId = row.data('registration-id');
         var recipientEmail = button.data('email');
         var name = row.find('td:first').text().trim(); // Trim whitespace

         $('#recipient_email').val(recipientEmail);
         $('#registration_id').val(registrationId);
         $('#is_mass_email').val('0');
         $('#event_id').val(''); // Clear event_id for single email

         // Fill recipient Name in Modal
         $('#admin_mail_recipient').text(name || recipientEmail); // Use email if name is empty

         // Pre-fill subject with event name
         var eventName = button.closest('.event-registrations').find('h2').text().split(' - ')[0];
         $('#email_subject').val('Regarding: ' + eventName);

         modal.show();
     });



    // Open modal for mass email
    $('.mail-to-all').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var eventId = button.data('event-id');
        var eventName = button.data('event-name');

        $('#event_id').val(eventId);
        $('#is_mass_email').val('1');
        $('#recipient_email').val(''); // Clear single recipient fields
        $('#registration_id').val('');

        $('#admin_mail_recipient').text('All Registrants'); // More descriptive

        $('#email_subject').val('Regarding: ' + eventName);

        modal.show();
    });

     // Close modal when X or Cancel is clicked
     $('.close-modal, .cancel-email').on('click', function(e) {
         e.preventDefault();
         closeModal();
     });

     // Close modal when clicking outside
     modal.on('click', function(e) {
         // Check if the click was on the modal background (not the content)
         if ($(e.target).is(modal)) {
             closeModal(); // Close if background is clicked
         }
     });

     // Prevent modal from closing when clicking inside the modal content
     $('.email-modal-content').on('click', function(e) {
         e.stopPropagation();
     });

     // Function to close modal and reset form
     function closeModal() {
        modal.hide();
        var form = $('#send-email-form');
        if (form.length) {
            form[0].reset();
            // Clear all hidden fields too
            $('#recipient_email').val('');
            $('#registration_id').val('');
            $('#event_id').val('');
            $('#is_mass_email').val('');
            $('#admin_mail_recipient').text(''); // Clear recipient display
        }
     }

     // Handle email form submission
     $('#send-email-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Sending...');

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // --- REPLACED alert ---
                    showAdminNotice(response.data.message || 'Email sent successfully.', 'success');
                    closeModal();
                } else {
                    // --- REPLACED alert ---
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                // --- REPLACED alert ---
                showAdminNotice('Error sending email: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Handle retry email button click (from failed log)
    $('.retry-email').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const email = button.data('email');
        const eventId = button.data('event-id');
        const nonce = button.data('nonce');

        // Disable button and show loading state
        button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sct_retry_single_email',
                email: email,
                event_id: eventId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // --- REPLACED alert ---
                    showAdminNotice('Email sent successfully!', 'success');
                    // Optionally refresh the page or update UI
                    location.reload(); // Reload to update the log status
                } else {
                    // --- REPLACED alert ---
                    showAdminNotice('Failed to send email: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                // --- REPLACED alert ---
                showAdminNotice('Error occurred while sending email. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                // Re-enable button and restore text
                button.prop('disabled', false).text('Retry');
            }
        });
    });

    $('.view-email-content').click(function() {
        const subject = $(this).data('subject');
        const content = $(this).data('content'); // Assuming this is plain text

        $('#modal-email-subject').text(subject);
        // Display raw HTML content if it's HTML, otherwise text
        // Basic check for HTML tags
        if (/<[a-z][\s\S]*>/i.test(content)) {
             $('#email-content').html(content); // Render as HTML
        } else {
            $('#email-content').text(content); // Display as plain text
        }
        $('#email-content-modal').show();
    });

    // View recipients handler
    $('.view-recipients').click(function() {
        const recipientsDataRaw = $(this).data('recipients');
        if (!recipientsDataRaw) {
            console.error('No recipients data found');
            return;
        }
        const recipientsData = recipientsDataRaw.split('|');
        const subject = $(this).data('subject');
        const eventId = $(this).data('event-id');
        const wp_nonce = $(this).data('nonce'); // Make sure this is set in your PHP

        $('#modal-recipients-title').text('Recipients for: ' + subject);

        let recipientsList = '<ul>';
        recipientsData.forEach(function(recipientData) {
            if (!recipientData) return; // Skip empty entries
            const [email, status] = recipientData.split(':');
            const statusClass = status === 'failed' ? 'failed-status' : 'success-status';
            const statusIcon = status === 'failed' ?
                '<span class="dashicons dashicons-warning" style="color: #d63638;"></span>': // Red for failed
                '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>'; // Green for success

            recipientsList += `
                <li class="${statusClass}">
                    ${statusIcon}
                    <span class="recipient-email">${email}</span>
                    <span class="status-label">${status === 'failed' ? 'Failed' : 'Sent'}</span>
                    ${status === 'failed' ? `
                        <button class="button button-small retry-single-email"
                                data-email="${email}"
                                data-event-id="${eventId}"
                                data-nonce="${wp_nonce}">
                            <span class="dashicons dashicons-update"></span>
                            Retry
                        </button>
                    ` : ''}
                </li>`;
        });
        recipientsList += '</ul>';

        $('#recipients-list').html(recipientsList);
        $('#recipients-modal').show();
    });


    // Close recipients modal and other modals
    $(document).on('click', '.close-modal', function(event) {
         $(this).closest('.modal').hide();
    });
    $(document).on('click', '.modal', function(event) {
        // Close only if the background itself (the .modal element) is clicked
        if (event.target === this) {
            $(this).hide();
        }
    });
    // Prevent clicks inside modal content from closing the modal
    $(document).on('click', '.modal-content, .email-modal-content', function(event) {
        event.stopPropagation();
    });


    // Helper function to show notices inside the recipients modal
    function showNoticeInModal(message, type, modalSelector) {
        const noticeHtml = `
            <div class="notice notice-${type} is-dismissible inline-notice modal-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>`;

        // Remove any existing notices in the target modal
        $(modalSelector + ' .modal-notice').remove();

        // Prepend the notice inside the modal's list or main content area
        var noticeTarget = $(modalSelector + ' .modal-body'); // Adjust selector if needed
        if (noticeTarget.length === 0) {
            noticeTarget = $(modalSelector + ' .modal-content'); // Fallback
        }
        noticeTarget.prepend(noticeHtml);


        // Add dismiss functionality for the modal notice
        $(modalSelector + ' .notice-dismiss').on('click', function() {
            $(this).closest('.modal-notice').fadeOut(function() {
                $(this).remove();
            });
        });

        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $(modalSelector + ' .modal-notice').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Retry single email handler (within recipients modal)
    $(document).on('click', '.retry-single-email', function(e) {
        e.preventDefault();
        const button = $(this);
        const email = button.data('email');
        const eventId = button.data('event-id');
        const nonce = button.data('nonce');

        // Add spinning animation to button
        button.prop('disabled', true)
            .find('.dashicons-update')
            .addClass('spinning');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sct_retry_single_email',
                email: email,
                event_id: eventId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the list item to show success
                    const listItem = button.closest('li');
                    listItem.removeClass('failed-status').addClass('success-status');
                    listItem.find('.dashicons-warning').removeClass('dashicons-warning dashicons-no').addClass('dashicons-yes-alt').css('color', '#46b450');
                    listItem.find('.status-label').text('Sent');
                    button.remove(); // Remove the retry button on success

                    // Show success message inside the modal
                    showNoticeInModal('Email resent successfully to ' + email, 'success', '#recipients-modal');
                } else {
                    button.prop('disabled', false)
                        .find('.dashicons-update')
                        .removeClass('spinning');
                    showNoticeInModal(response.data.message || 'Failed to resend email', 'error', '#recipients-modal');
                }
            },
            error: function() {
                button.prop('disabled', false)
                    .find('.dashicons-update')
                    .removeClass('spinning');
                showNoticeInModal('Server error occurred while retrying email.', 'error', '#recipients-modal');
            }
        });
    });

    function calculateTotals(table) {
        if (!table || table.length === 0) return; // Exit if table doesn't exist

        var totalPrice = 0;
        var totalGuests = 0; // Combined total
        var totalMembers = 0;
        var totalNonMembers = 0;
        var totalChildren = 0;
        var totalFromPricingOptions = 0; // For pricing options specifically

        var currencySymbol = eventAdmin.currency_symbol || '$'; // Use localized symbol
        var currencyPosition = eventAdmin.currency_position || 'before'; // 'before' or 'after'
        var decimalSeparator = eventAdmin.decimal_separator || '.';
        var thousandSeparator = eventAdmin.thousand_separator || ',';
        var numDecimals = parseInt(eventAdmin.number_of_decimals, 10) || 2;

        // Function to format currency
        function formatCurrency(amount) {
            let formattedAmount = parseFloat(amount).toFixed(numDecimals);
            let parts = formattedAmount.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
            formattedAmount = parts.join(decimalSeparator);

            if (currencyPosition === 'before') {
                return currencySymbol + formattedAmount;
            } else {
                return formattedAmount + currencySymbol;
            }
        }

        // Calculate totals based on visible rows in DataTable (if applicable) or all rows
        var rowsToCalculate;
        if ($.fn.DataTable.isDataTable(table)) {
            rowsToCalculate = table.DataTable().rows({ search: 'applied' }).nodes();
        } else {
            rowsToCalculate = table.find('tbody tr');
        }

        $(rowsToCalculate).each(function() {
            var row = $(this);
            var rowTotalPrice = 0;

            // Check if using pricing options
            var pricingInputs = row.find('.pricing-option-guest-count');
            if (pricingInputs.length > 0) {
                let rowGuestCount = 0;
                pricingInputs.each(function() {
                    const count = parseInt($(this).val(), 10) || 0;
                    const price = parseFloat($(this).data('price')) || 0; // Get price from data attribute
                    rowTotalPrice += count * price;
                    rowGuestCount += count;
                });
                totalFromPricingOptions += rowGuestCount; // Add to pricing option specific total
                totalGuests += rowGuestCount; // Add to overall total guests
                row.find('.total-guests').text(rowGuestCount); // Update row's total guests display
            } else {
                // Using member/non-member/children counts
                var memberGuests = parseInt(row.find('.member-guest-count').val(), 10) || 0;
                var nonMemberGuests = parseInt(row.find('.non-member-guest-count').val(), 10) || 0;
                var childrenGuests = parseInt(row.find('.children-guest-count').val(), 10) || 0;

                var memberPrice = parseFloat(row.data('member-price')) || 0;
                var nonMemberPrice = parseFloat(row.data('non-member-price')) || 0;
                // Assuming children price is handled elsewhere or is 0 if not specified

                rowTotalPrice = (memberPrice * memberGuests) + (nonMemberPrice * nonMemberGuests);
                totalMembers += memberGuests;
                totalNonMembers += nonMemberGuests;
                totalChildren += childrenGuests;
                totalGuests += memberGuests + nonMemberGuests + childrenGuests; // Add all types to total guests
            }

            // Update row's total price display
            row.find('.total-price-cell .total-price').text(formatCurrency(rowTotalPrice));
            totalPrice += rowTotalPrice; // Add row total to grand total price
        });


        // Update footer totals
        var footer = table.find('tfoot');
        footer.find('.table-total-price .total-price-value').text(formatCurrency(totalPrice));

        // Update specific counts if those columns exist in the footer
        if (footer.find('.total-members').length) footer.find('.total-members').text(totalMembers);
        if (footer.find('.total-non-members').length) footer.find('.total-non-members').text(totalNonMembers);
        if (footer.find('.total-children').length) footer.find('.total-children').text(totalChildren);
        if (footer.find('.total-pricing-options').length) footer.find('.total-pricing-options').text(totalFromPricingOptions); // Update pricing option total if exists

        // Update overall total guests count in the footer
        footer.find('.total-guests').text(totalGuests);


        // Update Remaining Capacity
        var eventCapacity = parseInt(table.data('event-capacity'), 10);
        var remainingCapacityCell = footer.find('.remaining-capacity');
        remainingCapacityCell.removeClass('status-indicator status-failed status-warning'); // Clear previous status classes

        if (!isNaN(eventCapacity) && eventCapacity > 0) {
            var remaining = eventCapacity - totalGuests;
            if (remaining < 0) {
                remainingCapacityCell.text('Over Capacity (' + remaining + ')').addClass('status-indicator status-failed');
            } else if (remaining === 0) {
                remainingCapacityCell.text('Sold Out').addClass('status-indicator status-failed');
            } else {
                 // Optional: Add warning if capacity is low
                 if (remaining <= (eventCapacity * 0.1)) { // Example: warning if <= 10% remaining
                     remainingCapacityCell.addClass('status-indicator status-warning');
                 }
                remainingCapacityCell.text(remaining + ' Remaining');
            }
        } else if (eventCapacity === 0) {
             remainingCapacityCell.text('Unlimited'); // Or 'N/A' or empty
        } else {
            remainingCapacityCell.text(''); // Clear if capacity is not set or invalid
        }
    }

    // Initial calculation for all tables on page load
    $('.wp-list-table.registrations-table').each(function() { // Target specific tables if needed
        calculateTotals($(this));
    });

    // Recalculate totals when a guest count changes value and loses focus (or on input)
    $(document).on('change input', '.member-guest-count, .non-member-guest-count, .children-guest-count, .pricing-option-guest-count', function() {
        var table = $(this).closest('.wp-list-table');
        calculateTotals(table);
    });


    // Initialize DataTables on specific pages (Improved Targeting)
    if ($('body').hasClass('sct_event-administration_page_event-registrations') ||
        $('body').hasClass('sct_event-administration_page_event-past') ||
        $('body').hasClass('sct_event-administration_page_event-admin')) { // Check body classes set by WP

        $('.wp-list-table.registrations-table').each(function() { // Target specific tables
            var table = $(this);
            if (table.length && !$.fn.DataTable.isDataTable(table)) { // Check if table exists and not already initialized

                 // Custom sorting for input fields
                $.fn.dataTable.ext.order['dom-text-numeric'] = function (settings, col) {
                    return this.api().column(col, { order: 'index' }).nodes().map(function (td, i) {
                        var inputVal = $('input', td).val();
                        return parseFloat(inputVal) || 0; // Treat empty/non-numeric as 0 for sorting
                    });
                };
                 // Custom sorting for currency
                $.fn.dataTable.ext.order['dom-currency'] = function (settings, col) {
                    return this.api().column(col, { order: 'index' }).nodes().map(function (td, i) {
                        var text = $('span.total-price', td).text(); // Target the span
                        // Remove currency symbols, thousand separators, convert decimal separator
                        var val = text.replace(new RegExp('\\' + (eventAdmin.currency_symbol || '$'), 'g'), '')
                                      .replace(new RegExp('\\' + (eventAdmin.thousand_separator || ','), 'g'), '')
                                      .replace(new RegExp('\\' + (eventAdmin.decimal_separator || '.'), 'g'), '.');
                        return parseFloat(val) || 0;
                    });
                };

                var columnDefs = [
                    { "orderable": false, "targets": 'no-sort' }, // Use class 'no-sort' on TH for non-sortable columns (like Actions)
                    { "orderDataType": "dom-text-numeric", "targets": 'sort-input-numeric' }, // Use class 'sort-input-numeric' on TH for numeric input columns
                    { "orderDataType": "dom-currency", "targets": 'sort-currency' } // Use class 'sort-currency' on TH for currency columns
                ];

                var dt = table.DataTable({
                    "order": [], // Disable initial sorting by default
                    "columnDefs": columnDefs,
                    "lengthMenu": (function() {
                        var defaultLengths = [10, 25, 50, -1];
                        var displayLengths = [10, 25, 50, "All"];
                        var pageLengthSetting = parseInt(eventAdmin.items_per_page, 10);

                        if (pageLengthSetting && !defaultLengths.includes(pageLengthSetting)) {
                            // Find the correct position to insert based on numeric value
                            let inserted = false;
                            for (let i = 0; i < defaultLengths.length -1; i++) { // -1 to exclude 'All'
                                if (pageLengthSetting < defaultLengths[i]) {
                                    defaultLengths.splice(i, 0, pageLengthSetting);
                                    displayLengths.splice(i, 0, pageLengthSetting);
                                    inserted = true;
                                    break;
                                }
                            }
                            // If it's larger than all existing numbers, insert before 'All'
                            if (!inserted) {
                                defaultLengths.splice(defaultLengths.length - 1, 0, pageLengthSetting);
                                displayLengths.splice(displayLengths.length - 1, 0, pageLengthSetting);
                            }
                        }
                        return [defaultLengths, displayLengths];
                    })(),
                    "pageLength": parseInt(eventAdmin.items_per_page, 10) || 10, // Use setting or default
                    "language": {
                        "search": "Filter records:", // Customize search label
                         "lengthMenu": "Show _MENU_ entries",
                         "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                         "infoEmpty": "Showing 0 to 0 of 0 entries",
                         "infoFiltered": "(filtered from _MAX_ total entries)",
                         "paginate": {
                            "first":      "First",
                            "last":       "Last",
                            "next":       "Next",
                            "previous":   "Previous"
                        },
                        "zeroRecords": "No matching records found",
                        "emptyTable": "No data available in table"
                    },
                    "initComplete": function() {
                        var api = this.api();
                        // Default sort by Registration Date (find column index by TH text)
                        var registrationDateColumnIndex = api.column('th:contains("Registration Date")').index();
                        if (typeof registrationDateColumnIndex !== 'undefined') {
                            api.order([registrationDateColumnIndex, 'desc']).draw(); // Sort descending by default
                        }
                        // Recalculate totals after DataTables init/redraw
                        calculateTotals(table);
                    }
                });

                // Recalculate totals on DataTables draw events (search, pagination, sort)
                dt.on('draw.dt', function() {
                    calculateTotals(table);
                });
            }
        });
    }


    // Handle children guest count update visibility
    $('.children-guest-count').on('input change', function() { // Use input and change
        var $input = $(this);
        var $button = $input.siblings('.update-children-guest-count');
        if ($input.val() != $input.data('original')) {
            $button.show();
        } else {
            $button.hide();
        }
    });


    $('#accordion').accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    $('#add-pricing-option').on('click', function () {
        const index = $('#pricing-options-container .pricing-option').length;
        // Use number input for price, ensure step and min are set
        $('#pricing-options-container').append(`
            <div class="pricing-option">
                <label>Name: <input type="text" name="pricing_options[${index}][name]" placeholder="Category Name" required></label>
                <label>Price: <input type="number" name="pricing_options[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required></label>
                <button type="button" class="remove-pricing-option button button-small"><span class="dashicons dashicons-trash"></span> Remove</button>
            </div>
        `);
    });



    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();

        // Re-index the remaining pricing options to ensure sequential keys on submit
        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');
                // More robust regex to handle potential nested arrays if ever needed
                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    });


    // Consolidated function to handle guest count updates (Member/Non-Member/Children)
    function handleGuestCountUpdate(button, guestTypeClass) {
        var input = button.siblings('.' + guestTypeClass);
        var row = button.closest('tr');
        var registrationId = row.data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);

        // Validate count
        if (isNaN(newGuestCount) || newGuestCount < 0) {
             showAdminNotice('Please enter a valid number (0 or more).', 'warning');
             input.val(input.data('original')); // Revert to original
             button.hide();
             return;
        }

        var memberGuestCount = parseInt(row.find('.member-guest-count').val(), 10) || 0;
        var nonMemberGuestCount = parseInt(row.find('.non-member-guest-count').val(), 10) || 0;
        var childrenGuestCount = parseInt(row.find('.children-guest-count').val(), 10) || 0;

        var data = {
            action: 'update_registration', // Single action for all these types
            registration_id: registrationId,
            member_guests: memberGuestCount,
            non_member_guests: nonMemberGuestCount,
            children_guests: childrenGuestCount,
            security: eventAdmin.update_registration_nonce // Include the nonce
        };

        // Ensure the count being updated is correctly set in the data
        if (guestTypeClass === 'member-guest-count') data.member_guests = newGuestCount;
        if (guestTypeClass === 'non-member-guest-count') data.non_member_guests = newGuestCount;
        if (guestTypeClass === 'children-guest-count') data.children_guests = newGuestCount;


        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                button.prop('disabled', true).find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update spinning');
                button.find('.button-text').text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount); // Update original value
                    button.hide(); // Hide button on success

                    // Show temporary success checkmark
                    var successIcon = $('<span class="dashicons dashicons-yes" style="color: green; margin-left: 5px;"></span>');
                    button.after(successIcon);
                    setTimeout(function() {
                        successIcon.fadeOut(function() { $(this).remove(); });
                    }, 2000);

                    // Recalculate totals for the table
                    calculateTotals(button.closest('.wp-list-table'));

                } else {
                    // --- REPLACED alert ---
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    input.val(input.data('original')); // Revert on error
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                // --- REPLACED alert ---
                showAdminNotice('Error updating guest count: ' + error, 'error');
                input.val(input.data('original')); // Revert on error
            },
            complete: function() {
                // Restore button state
                button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spinning').addClass('dashicons-yes');
                button.find('.button-text').text('Update');
                // Button remains hidden here because success hides it, error reverts value and button should hide too
                if (input.val() == input.data('original')) {
                                button.hide();
                            }
                        }
                    });
                }

    // Handle guest count update button clicks (Member/Non-Member/Children)
    $('.update-member-guest-count, .update-non-member-guest-count, .update-children-guest-count').on('click', function() {
        var button = $(this);
        var guestTypeClass = button.attr('class').match(/(member|non-member|children)-guest-count/)[0]; // Get the input class name
        handleGuestCountUpdate(button, guestTypeClass);
    });

    // Open Add Registration Modal
    $('.add-registration').on('click', function () {
        const button = $(this);
        const eventId = button.data('event-id');
        const pricingOptions = button.data('pricing-options'); // Expecting an array of objects
        const modal = $('#add-registration-modal');
        const form = modal.find('#add-registration-form');

        // Clear previous form state
        form[0].reset();
        form.find('#add_registration_pricing_options_container').empty(); // Clear dynamic options
        form.find('#add_registration_guest_count_field').hide(); // Hide basic count field initially

        // Set the event ID in the hidden input field
        form.find('input[name="event_id"]').val(eventId);

        const pricingContainer = form.find('#add_registration_pricing_options_container');
        const basicGuestCountField = form.find('#add_registration_guest_count_field');

        // Build pricing options if available
        if (pricingOptions && Array.isArray(pricingOptions) && pricingOptions.length > 0) {
            pricingContainer.show();
            basicGuestCountField.hide(); // Hide basic count if pricing options exist
            pricingOptions.forEach((option, index) => {
                // Format price for display
                let priceDisplay = 'Free';
                const price = parseFloat(option.price);
                 if (!isNaN(price) && price > 0) {
                    // Use localized currency formatting if available
                    priceDisplay = (eventAdmin.currency_symbol || '$') + price.toFixed(eventAdmin.number_of_decimals || 2);
                 }

                pricingContainer.append(`
                    <div class="pricing-option form-field">
                        <label for="add_guest_count_${index}">
                            ${option.name} (${priceDisplay}):
                        </label>
                        <input type="number"
                               id="add_guest_count_${index}"
                               name="guest_details[${index}][count]"
                               class="small-text guest-count"
                               min="0"
                               value="0"
                               data-price="${option.price || 0}"> <!-- Store price for potential calculations -->
                        <input type="hidden" name="guest_details[${index}][name]" value="${option.name}">
                        <input type="hidden" name="guest_details[${index}][price]" value="${option.price || 0}">
                    </div>
                `);
            });
        } else {
            // Show basic guest count field if no pricing options
            pricingContainer.hide();
            basicGuestCountField.show();
        }

        // Show the modal
        modal.show();
    });

    // Handle Add Registration Form Submission
    $(document).on('submit', '#add-registration-form', function (e) {
        e.preventDefault(); // Prevent the default form submission

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Adding...');

        // Send the AJAX request
        $.ajax({
            url: eventAdmin.ajaxurl, // Ensure this is set correctly in your JavaScript
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.update_registration_nonce, // Use appropriate nonce
            success: function (response) {
                if (response.success) {
                    // --- REPLACED alert ---
                    showAdminNotice('Registration added successfully.', 'success');
                    $('#add-registration-modal').hide(); // Hide modal on success
                    location.reload(); // Reload the page to reflect the changes
                } else {
                    // --- REPLACED alert ---
                    // Show error within the modal if possible, or as a general admin notice
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                 // --- REPLACED alert ---
                showAdminNotice('Error adding registration: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });



    // Close Add Registration modal via Cancel button
    $(document).on('click', '#add-registration-modal .cancel-registration', function() {
        $(this).closest('.modal').hide();
    });


    // Handle select winners button click
    $(document).on('click', '.select-winners', function() {
        var eventId = $(this).data('event-id');
        $('#select_winners_event_id').val(eventId);
        $('#select-winners-modal').show();
    });

    // Handle select winners form submission
    $('#select-winners-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Selecting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.select_winners_nonce, // Add nonce
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Winners selected successfully. Refreshing...', 'success');
                    $('#select-winners-modal').hide();
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not select winners.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Error selecting winners: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });



    // Close select winners modal
    $('#select-winners-modal .close-modal, #select-winners-modal .cancel-selection').on('click', function() {
        $(this).closest('.modal').hide(); // Use .modal as the selector
    });


    // Handle export custom tables button click
    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce // Ensure this nonce is correctly localized
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    // Create a link and click it to trigger download
                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = response.data.filename || 'custom_tables_export.sql'; // Use filename from response
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    showAdminNotice('Export file generated successfully.', 'success');
                } else {
                     // --- REPLACED alert ---
                    showAdminNotice('Failed to export tables: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                 // --- REPLACED alert ---
                showAdminNotice('Error occurred while exporting tables. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                button.prop('disabled', false).text('Export Custom Tables');
            }
        });
    });



    // Handle form submission for saving event details (including pricing options)
    // Ensure your form has id="event-form" or adjust selector
    $('#event-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('input[type="submit"], button[type="submit"]'); // Find submit button
        const originalButtonText = submitButton.val() || submitButton.text(); // Get text/value

        submitButton.prop('disabled', true).val('Saving...').text('Saving...'); // Update text/value

        $.ajax({
            url: eventAdmin.ajaxurl, // Make sure ajaxurl is available
            type: 'POST',
            data: form.serialize(), // Includes pricing options due to naming convention
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Event saved successfully.', 'success');
                    // Optionally redirect or just indicate success
                    // window.location.href = response.data.redirect_url; // If PHP sends a redirect URL
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error saving event: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).val(originalButtonText).text(originalButtonText); // Restore text/value
            }
        });
    });

    // Handle updating guest counts (for pricing options or single count)
    $('.update-guest-counts').on('click', function () {
        const button = $(this);
        const row = button.closest('tr');
        const registrationId = row.data('registration-id'); // Get registration ID from row data attribute
        const nonce = eventAdmin.update_registration_nonce; // Get nonce

        let data = {
            registration_id: registrationId,
            security: nonce
        };

        const pricingOptionInputs = row.find('.pricing-option-guest-count');
        const guestCountInput = row.find('.guest-count'); // For non-pricing option case

        if (pricingOptionInputs.length > 0) {
            // Using Pricing Options
            data.action = 'update_registration_guest_counts'; // Specific action for pricing options
            
            let guestDetails = {};
            let totalGuests = 0;
            let isValid = true;

            pricingOptionInputs.each(function () {
                const input = $(this);
                const index = input.data('pricing-index'); // Assuming index is stored in data-pricing-index
                const count = parseInt(input.val(), 10);
                const name = input.closest('.pricing-option-details').find('input[name$="[name]"]').val(); // Get name
                const price = input.closest('.pricing-option-details').find('input[name$="[price]"]').val(); // Get price

                if (isNaN(count) || count < 0) {
                    showAdminNotice(`Invalid count entered for ${name || 'an option'}. Please enter 0 or more.`, 'warning');
                    input.val(input.data('original')); // Revert
                    isValid = false;
                    return false; // Exit .each loop
                }
                guestDetails[index] = { count: count, name: name, price: price }; // Store details
                totalGuests += count;
            });

            if (!isValid) {
                button.hide(); // Hide button if validation failed
                return; // Stop processing
            }

            data.guest_details = guestDetails; // Add collected details to data

        } else if (guestCountInput.length > 0) {
            // Using Single Guest Count
            data.action = 'update_registration_guest_count'; // Use the general update action
            const count = parseInt(guestCountInput.val(), 10);

            if (isNaN(count) || count < 0) {
                 showAdminNotice('Guest count must be 0 or more.', 'warning');
                 guestCountInput.val(guestCountInput.data('original')); // Revert
                 button.hide();
                 return; // Stop processing
            }
            data.guest_count = count; // Add single count to data
        } else {
            console.error('Could not find guest count inputs for this row.');
            return; // No inputs found
        }

        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function () {
                 button.prop('disabled', true).find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update spinning');
                 button.find('.button-text').text('Updating...');
            },
            success: function (response) {
                if (response.success) {
                    // Update original values on inputs
                    if (pricingOptionInputs.length > 0) {
                        pricingOptionInputs.each(function() {
                            $(this).data('original', $(this).val());
                        });
                    } else if (guestCountInput.length > 0) {
                        guestCountInput.data('original', guestCountInput.val());
                    }

                    button.hide(); // Hide button on success

                    // Show temporary success checkmark
                    showAdminNotice('Guest counts updated successfully.', 'success');
                    var successIcon = $('<span class="dashicons dashicons-yes center" style="color: green; line-height: 32px;"></span>');
                    button.after(successIcon);
                    setTimeout(function() {
                        successIcon.fadeOut(function() { $(this).remove(); });
                    }, 2000);

                    // Recalculate totals for the table
                    calculateTotals(button.closest('.wp-list-table'));

                } else {
                     // --- REPLACED alert ---
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    // Revert values on error
                    if (pricingOptionInputs.length > 0) {
                        pricingOptionInputs.each(function() { $(this).val($(this).data('original')); });
                    } else if (guestCountInput.length > 0) {
                        guestCountInput.val(guestCountInput.data('original'));
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                 // --- REPLACED alert ---
                showAdminNotice('Error updating guest count. Please try again.', 'error');
                 // Revert values on error
                 if (pricingOptionInputs.length > 0) {
                    pricingOptionInputs.each(function() { $(this).val($(this).data('original')); });
                } else if (guestCountInput.length > 0) {
                    guestCountInput.val(guestCountInput.data('original'));
                }
            },
            complete: function () {
                 // Restore button state
                 button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spinning').addClass('dashicons-yes');
                 button.find('.button-text').text('Update');
                 // Hide button if values match original after potential revert
                 let changed = false;
                 if (pricingOptionInputs.length > 0) {
                    pricingOptionInputs.each(function() { if ($(this).val() != $(this).data('original')) changed = true; });
                 } else if (guestCountInput.length > 0) {
                    if (guestCountInput.val() != guestCountInput.data('original')) changed = true;
                 }
                 if (!changed) button.hide();
            }
        });
    });


    // --- Commented out block with alerts remains commented ---
    // Handle the "Copy" button click
    // $('#copy-selected-event').on('click', function () {
    //     const selectedEventName = $('#previous-events-dropdown').val();
    //     const newEventDate = prompt('Enter the new date for the copied event (YYYY-MM-DD):');
    //
    //     if (!selectedEventName || !newEventDate) {
    //         alert('Please select an event and provide a new date.');
    //         return;
    //     }
    //
    //     // Send AJAX request to copy the event
    //     $.ajax({
    //         url: ajaxurl,
    //         type: 'POST',
    //         data: {
    //             action: 'copy_event_by_name',
    //             event_name: selectedEventName,
    //             new_event_date: newEventDate,
    //             security: eventAdmin.copy_event_nonce
    //         },
    //         success: function (response) {
    //             if (response.success) {
    //                 alert('Event copied successfully.');
    //                 location.reload();
    //             } else {
    //                 alert(response.data.message || 'Failed to copy event.');
    //             }
    //         },
    //         error: function () {
    //             alert('An error occurred while copying the event.');
    //         }
    //     });
    // });
    // --- End commented block ---

}); // End Ready Function
