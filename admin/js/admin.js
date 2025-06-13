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
    // $(document).on('click', '.close-modal', function () {
    //     $(this).closest('.modal').hide();
    //     clearModalFields(); // Clear the modal fields when closed
    // });

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
    $('.qqqplaceholder-info code').each(function() {
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

    // Add click handlers for the placeholder codes
    $('.placeholder-info code').each(function() {
        $(this)
            .css('cursor', 'pointer')
            .attr('title', 'Click to insert')
            .on('click', function() {
                var placeholder = $(this).text() + ' ';
                var editorId = 'confirmation_template';

                // Check if TinyMCE is active
                if (typeof tinymce !== 'undefined' && tinymce.get(editorId) && !tinymce.get(editorId).isHidden()) {
                    // Insert into the Visual editor
                    tinymce.get(editorId).execCommand('mceInsertContent', false, placeholder);
                } else {
                    // Insert into the Text editor
                    var $textarea = $('#' + editorId);
                    var startPos = $textarea[0].selectionStart;
                    var endPos = $textarea[0].selectionEnd;
                    var currentContent = $textarea.val();
                    var newContent = currentContent.substring(0, startPos) +
                        placeholder +
                        currentContent.substring(endPos);
                    $textarea.val(newContent);
                    $textarea.focus();
                    $textarea[0].setSelectionRange(startPos + placeholder.length, startPos + placeholder.length);
                }
            });
    });


    $('.simple-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var row = input.closest('tr'); // Find the closest <tr> for the input
        var updateButton = row.find('.update-simple-guest-count'); // Find the button within the same <tr>

        console.log('Update button found:', updateButton.length > 0);
        console.log('Original value:', originalValue, 'Current value:', input.val());

        // Show or hide the update button based on whether the value has changed
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const input = $(this);
        const row = input.closest('tr'); // Find the closest <tr> for the input
        const originalValue = input.data('original');
        const updateButton = row.find('.update-guest-counts'); // Find the button within the same <tr>

        console.log('Update button found:', updateButton.length > 0);
        console.log('Original value:', originalValue, 'Current value:', input.val());

        // Show or hide the update button based on whether the value has changed
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }

        // Update the Total Price for the row
        updateRowTotalPrice(row);
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
         var recipientEmail = button.data('recipient-email');
         var name = row.find('td:first').text().trim(); // Trim whitespace

         $('#send-email-form #recipient_email').val(recipientEmail);
         $('#send-email-form #registration_id').val(registrationId);
         $('#send-email-form #is_mass_email').val('0');
         $('#send-email-form #event_id').val(''); // Clear event_id for single email

         // Fill recipient Name in Modal
         $('#email-modal #admin_mail_recipient').text(name || recipientEmail); // Use email if name is empty

         // Pre-fill subject with event name
         var eventName = button.closest('.event-registrations').find('h2').text().split(' - ')[0];
         $('#send-email-form #email_subject').val('Regarding: ' + eventName);
         $('#send-email-form #email_body').val('');


         modal.show();
     });


     UIkit.util.on('div[uk-modal]', 'beforeshow', function (e) {
        const modalId = $(this).attr('id'); // Get the modal ID
        if (modalId === 'email-modal') {
            console.log('Preparing Email Modal...');
            $('#send-email-form  #email_body').html(''); // Clear message field

            // const form = $(`#${modalId} form`);
            // if (form.length) {
            //     $('#email-message').val(''); // Clear message field
            //     // form[0].reset();
            // }
        }
        // Perform any custom logic before the modal is shown
        // if (modalId === 'add-registration-modal') {
        //     console.log('Preparing Add Registration Modal...');
        //     // Example: Reset form fields
        //     const form = $(`#${modalId} form`);
        //     if (form.length) {
        //         form[0].reset();
        //     }
        // } else if (modalId === 'select-winners-modal') {
        //     console.log('Preparing Select Winners Modal...');
        //     // Example: Set default values or fetch data
        // }
    });

    // Open modal for mass email
    $('.mail-to-all').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var eventId = button.data('event-id');
        var eventName = button.data('event-name');

        $('#send-email-form #event_id').val(eventId);
        $('#send-email-form #is_mass_email').val('1');
        $('#send-email-form #recipient_email').val(''); // Clear single recipient fields
        $('#send-email-form #registration_id').val('');

        $('#email-modal #admin_mail_recipient').text('All Registrants'); // More descriptive

        $('#send-email-form #email_subject').val('Regarding: ' + eventName);
        $('#send-email-form #email_body').val('');
        // modal.show();
    });

     // Close modal when X or Cancel is clicked
    //  $('.close-modal, .cancel-email').on('click', function(e) {
    //      e.preventDefault();
    //      closeModal();
    //  });

     // Close modal when clicking outside
    //  modal.on('click', function(e) {
    //      // Check if the click was on the modal background (not the content)
    //      if ($(e.target).is(modal)) {
    //          closeModal(); // Close if background is clicked
    //      }
    //  });

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
            $('#send-email-form #recipient_email').val('');
            $('#send-email-form #registration_id').val('');
            $('#send-email-form #event_id').val('');
            $('#send-email-form #is_mass_email').val('');
            $('#send-email-form #admin_mail_recipient').text(''); // Clear recipient display
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
        let totalGuests = 0;
        let totalPrice = 0;

        // Reset totals for pricing options and goods/services
        table.find('.total-pricing-option').text(0);
        table.find('.total-goods-service').text(0);

        // Calculate totals for each row
        table.find('tbody tr').each(function () {
            const row = $(this);
            let rowGuestCount = 0;

            // Add guest counts from pricing options
            row.find('.pricing-option-guest-count').each(function () {
                const count = parseInt($(this).val(), 10) || 0;
                rowGuestCount += count;

                // Update column totals for pricing options
                const index = $(this).data('pricing-index');
                const columnTotalCell = table.find(`.total-pricing-option[data-pricing-index="${index}"]`);
                const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
                columnTotalCell.text(currentTotal + count);
            });

            // Add guest counts from goods/services
            row.find('.goods-service-count').each(function () {
                const count = parseInt($(this).val(), 10) || 0;
                rowGuestCount += count;

                // Update column totals for goods/services
                const index = $(this).data('service-index');
                const columnTotalCell = table.find(`.total-goods-service[data-service-index="${index}"]`);
                const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
                columnTotalCell.text(currentTotal + count);
            });

            // If no pricing options or goods/services, use the guest_count field
            if (rowGuestCount === 0) {
                rowGuestCount = parseInt(row.find('.total-guests').text(), 10) || 0;
            }

            // Update row totals
            totalGuests += rowGuestCount;
        });

        // Update footer totals
        table.find('.event-total-guests').text(totalGuests);
    }

    // Initial calculation for all tables on page load
    $('.wp-list-table.registrations-table').each(function() { // Target specific tables if needed
        calculateTotals($(this));
    });

    // Recalculate totals when a guest count changes value and loses focus (or on input)
    $(document).on('change input', '.member-guest-count, .non-member-guest-count, .children-guest-count, .pricing-option-guest-count, .simple-guest-count', function() {
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
    
    // Handle simple guest count update visibility
    // $('.simple-guest-count').on('input change', function() {
    //     var $input = $(this);
    //     var $button = $input.siblings('.update-simple-guest-count');
    //     if ($input.val() != $input.data('original')) {
    //         $button.show();
    //     } else {
    //         $button.hide();
    //     }
    // });


    $('#accordion').accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    // $('#add-pricing-option').on('click', function () {
    //     const index = $('#pricing-options-container .pricing-option').length;
    //     // Use number input for price, ensure step and min are set
    //     $('#pricing-options-container').append(`
    //         <div class="pricing-option">
    //             <input type="text" name="pricing_options[${index}][name]" placeholder="Category Name" required>
    //             <input type="number" name="pricing_options[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
    //             <button type="button" class="remove-pricing-option button button-small"><span class="dashicons dashicons-trash"></span> Remove</button>
    //         </div>
    //     `);
    // });



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
    // $('.update-member-guest-count, .update-non-member-guest-count, .update-children-guest-count').on('click', function() {
    //     var button = $(this);
    //     var guestTypeClass = button.attr('class').match(/(member|non-member|children)-guest-count/)[0]; // Get the input class name
    //     handleGuestCountUpdate(button, guestTypeClass);
    // });
    
    // Handle simple guest count update button clicks
    $(document).on('click', '.update-simple-guest-count', function() {
        var button = $(this);
        var row = button.closest('tr');
        var input = row.find('.simple-guest-count');
        // var input = button.siblings('.simple-guest-count');
        // var row = button.closest('tr');
        var registrationId = button.data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);
        
        // Validate count
        if (isNaN(newGuestCount) || newGuestCount < 1) {
            showAdminNotice('Please enter a valid number (1 or more).', 'warning');
            input.val(input.data('original')); // Revert to original
            button.hide();
            return;
        }
        
        // Send AJAX request
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_registration_guest_count',
                registration_id: registrationId,
                guest_count: newGuestCount,
                security: eventAdmin.update_registration_nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
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
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    input.val(input.data('original')); // Revert on error
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest count: ' + error, 'error');
                input.val(input.data('original')); // Revert on error
            },
            complete: function() {
                button.prop('disabled', false);
                if (input.val() == input.data('original')) {
                    button.hide();
                }
            }
        });
    });

    // Open Add Registration Modal
    $('.add-registration').on('click', function () {
        const button = $(this);
        const eventId = button.data('event-id');
        const pricingOptions = button.data('pricing-options'); // Expecting an array of objects
        const goodsServices = button.data('goods-services'); // Expecting an array of objects
        const modal = $('#add-registration-modal');
        const form = modal.find('#add-registration-form');

        // Clear previous form state
        form[0].reset();
        form.find('#add_registration_pricing_options_container').empty(); // Clear dynamic options
        form.find('#add_registration_goods_services_container').empty(); // Clear dynamic goods/services options
        form.find('#add_registration_guest_count_field').hide(); // Hide basic count field initially

        // Set the event ID in the hidden input field
        form.find('input[name="event_id"]').val(eventId);

        const pricingContainer = form.find('#add_registration_pricing_options_container');
        const goodsServicesContainer = form.find('#add_registration_goods_services_container');
        const basicGuestCountField = form.find('#add_registration_guest_count_field');

        // Populate pricing options
        if (pricingOptions && pricingOptions.length > 0) {
            pricingOptions.forEach((option, index) => {
                pricingContainer.append(`
                    <div class="pricing-option">
                        <label>${option.name} (${option.price}):</label>
                        <input type="number" name="guest_details[${index}][count]" min="0" value="0">
                        <input type="hidden" name="guest_details[${index}][name]" value="${option.name}">
                        <input type="hidden" name="guest_details[${index}][price]" value="${option.price}">
                    </div>
                `);
            });
            pricingContainer.show();
            basicGuestCountField.hide();
        } else {
            // Show basic guest count field if no pricing options
            pricingContainer.hide();
            basicGuestCountField.show();
        }

        // Populate goods/services options
        if (goodsServices && goodsServices.length > 0) {
            goodsServices.forEach((service, index) => {
                goodsServicesContainer.append(`
                    <div class="goods-service-option">
                        <label>${service.name} (${service.price}):</label>
                        <input type="number" name="goods_services[${index}][count]" min="0" value="0">
                        <input type="hidden" name="goods_services[${index}][name]" value="${service.name}">
                        <input type="hidden" name="goods_services[${index}][price]" value="${service.price}">
                    </div>
                `);
            });
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
                    showAdminNotice('Registration added successfully.', 'success');
                    $('#add-registration-modal').hide(); // Hide modal on success
                    location.reload(); // Reload the page to reflect the changes
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
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
    $(document).on('click', '.update-guest-counts', function () {
        const button = $(this);
        const row = button.closest('tr');
        const registrationId = row.data('registration-id'); // Get registration ID from row data attribute
        const nonce = eventAdmin.update_registration_nonce; // Get nonce

        let data = {
            action: 'update_registration_guest_counts',
            registration_id: registrationId,
            security: nonce,
            guest_details: [],
            goods_services: []
        };

        // Collect guest details (pricing options)
        const pricingOptionInputs = row.find('.pricing-option-guest-count');
        pricingOptionInputs.each(function () {
            const input = $(this);
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); // Retrieve name from data attribute
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.guest_details.push({
                count: count,
                name: name,
                price: price
            });
        });

        // Collect goods/services details
        const goodsServiceInputs = row.find('.goods-service-count');
        goodsServiceInputs.each(function () {
            const input = $(this);
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); // Retrieve name from data attribute
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.goods_services.push({
                count: count,
                name: name,
                price: price
            });
        });

        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function () {
                button.prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {
                    // Update original values on inputs
                    pricingOptionInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });
                    goodsServiceInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });

                    button.hide(); // Hide button on success
                    showAdminNotice('Guest counts updated successfully.', 'success');
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest counts. Please try again.', 'error');
            },
            complete: function () {
                button.prop('disabled', false).text('Update');
            }
        });
    });

    function formatCurrency(amount, currencySymbol, currencyFormat) {
        const decimalSeparator = '.';
        const thousandSeparator = ',';

        // Format the amount with the specified number of decimals
        let formattedAmount = parseFloat(amount).toFixed(currencyFormat);
        let parts = formattedAmount.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        formattedAmount = parts.join(decimalSeparator);

        // Return the formatted amount with the currency symbol
        return currencySymbol + formattedAmount;
    }

    function updateRowTotalPrice(row) {
        let totalPrice = 0;

        // Retrieve currency settings from the DOM
        const currencySymbol = $('#currency-symbol').data('currency-symbol') || '$';
        const currencyFormat = $('#currency-format').data('currency-format') === 0 ? 0 : parseInt($('#currency-format').data('currency-format'), 10) || 2;

        // Calculate total price from pricing options
        row.find('.pricing-option-guest-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).closest('td').data('price')) || 0;
            totalPrice += count * price;
        });

        // Calculate total price from goods/services
        row.find('.goods-service-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).closest('td').data('price')) || 0;
            totalPrice += count * price;
        });

        // Format and update the Total Price column in the row
        row.find('.total-price').text(formatCurrency(totalPrice, currencySymbol, currencyFormat));
    }

    // Update all rows on page load
    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    // Update total price dynamically when inputs change
    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const row = $(this).closest('tr');
        const table = row.closest('.wp-list-table'); // Get the specific table
        updateRowTotalPrice(row); // Update the row's total price
        updateTableTotals(table); // Update the table footer totals
    });

    // $('#add-goods-service-option').on('click', function () {
    //     const index = $('#goods-services-container .goods-service-option').length;
    //     $('#goods-services-container').append(`
    //         <div class="goods-service-option">
    //             <input type="text" name="goods_services[${index}][name]" placeholder="Item Name" required>
    //             <input type="number" name="goods_services[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
    //             <input type="number" name="goods_services[${index}][limit]" placeholder="Limit (0 for unlimited)" min="0">
    //             <button type="button" class="remove-goods-service-option button">Remove</button>
    //         </div>
    //     `);
    // });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
    });

    // Close modal when clicking the 'x' button
    $(document).on('click', '.close-modal', function () {
        $(this).closest('.email-modal').hide();
    });

    // Close modal when clicking the cancel button
    $(document).on('click', '.cancel-registration', function () {
        $('#add-registration-modal').hide();
    });

    // Close modal when clicking outside the modal content
    // $(document).on('click', '.modal', function (event) {
    //     if ($(event.target).hasClass('email-modal')) {
    //         $(this).hide();
    //     }
    // });

    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    function updateTableTotals(table) {
        let totalGuests = 0;
        let totalPrice = 0;
        const currencySymbol = $('#currency-symbol').data('currency-symbol') || '$';
        const currencyFormat = $('#currency-format').data('currency-format') === 0 ? 0 : parseInt($('#currency-format').data('currency-format'), 10) || 2;

        // Reset totals for pricing options and goods/services in the specific table
        table.find('.total-pricing-option').text(0);
        table.find('.total-goods-service').text(0);



        if (table.find('.simple-guest-count').length != 0) {
            table.find('.simple-guest-count').each(function () {
                totalGuests += parseInt($(this).val());
            });
        }
        // Update totals for pricing options
        table.find('.pricing-option-guest-count').each(function () {
            const input = $(this);
            const row = input.closest('tr');
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;

            // Update column total
            const columnTotalCell = table.find(`.total-pricing-option[data-pricing-index="${index}"]`);
            const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
            columnTotalCell.text(currentTotal + count);

            // Update total guests
            totalGuests += count;
        });

        // Update totals for goods/services
        table.find('.goods-service-count').each(function () {
            const input = $(this);
            const row = input.closest('tr');
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;

            // Update column total
            const columnTotalCell = table.find(`.total-goods-service[data-service-index="${index}"]`);
            const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
            columnTotalCell.text(currentTotal + count);

            // Update total guests
            // totalGuests += count;
        });

        // Update total price
        table.find('tbody tr').each(function () {
            const row = $(this);
            const rowTotalPrice = parseFloat(row.find('.total-price').text().replace(/[^0-9.-]+/g, '')) || 0;
            totalPrice += rowTotalPrice;
        });

        // Update footer totals for the specific table
        table.find('.event-total-guests').text(totalGuests);
        table.find('.event-total-price').text(formatCurrency(totalPrice, currencySymbol, currencyFormat));
    }

    // Bind the updateTableTotals function to input changes
    $(document).on('input', '.pricing-option-guest-count, .goods-service-count, .simple-guest-count', function () {
        const row = $(this).closest('tr');
        updateRowTotalPrice(row); // Update the row's total price
        updateTableTotals(row.closest('.wp-list-table')); // Update the table footer totals
    });

    // Initialize totals on page load
    $(document).ready(function () {
        $('.wp-list-table').each(function () {
            const table = $(this);
            table.find('tbody tr').each(function () {
                updateRowTotalPrice($(this)); // Ensure row totals are calculated
            });
            updateTableTotals(table); // Ensure footer totals are calculated for the specific table
        });
    });

    $('#add-payment-method').on('click', function () {
        const index = $('#payment-methods-list .payment-method').length;
        $('#payment-methods-list').append(`
            <div class="payment-method">
                <select name="payment_methods[${index}][type]" required>
                    <option value="online">Online</option>
                    <option value="transfer">Transfer</option>
                    <option value="cash">Cash</option>
                </select>
                <input type="text" name="payment_methods[${index}][description]" placeholder="Description" required>
                <input type="url" name="payment_methods[${index}][link]" placeholder="Payment Link (for online)">
                <textarea name="payment_methods[${index}][transfer_details]" placeholder="Transfer Details (for transfer)" rows="5" required></textarea>
                <button type="button" class="remove-payment-method button">Remove</button>
            </div>
        `);
    });

    $(document).on('click', '.remove-payment-method', function () {
        $(this).closest('.payment-method').remove();
    });

    // Function to toggle the payment methods section
    function togglePaymentMethods() {
        const pricingOptionsCount = $('#pricing-options-container .pricing-option').length;
        const goodsServicesCount = $('#goods-services-container .goods-service-option').length;

        if (pricingOptionsCount > 0 || goodsServicesCount > 0) {
            $('#payment-methods-container').show();
        } else {
            $('#payment-methods-container').hide();
            clearPaymentMethods(); // Clear payment methods when hidden
        }
    }

    // Function to clear payment methods
    function clearPaymentMethods() {
        $('#payment-methods-list').empty(); // Remove all payment methods
    }

    // Add Pricing Option
    $(document).on('click', '#add-pricing-option', function () {
        const index = $('#pricing-options-container .pricing-option').length;
        $('#pricing-options-container').append(`
            <div class="pricing-option">
                <input type="text" name="pricing_options[${index}][name]" placeholder="Category Name" required>
                <input type="number" name="pricing_options[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <button type="button" class="remove-pricing-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); // Check if payment methods should be shown
    });

    // Remove Pricing Option
    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();
        reindexPricingOptions(); // Reindex remaining pricing options
        togglePaymentMethods(); // Check if payment methods should be hidden
    });

    // Add Goods/Service Option
    $(document).on('click', '#add-goods-service-option', function () {
        const index = $('#goods-services-container .goods-service-option').length;
        $('#goods-services-container').append(`
            <div class="goods-service-option">
                <input type="text" name="goods_services[${index}][name]" placeholder="Item Name" required>
                <input type="number" name="goods_services[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <input type="number" name="goods_services[${index}][limit]" placeholder="Limit (0 for unlimited)" min="0">
                <button type="button" class="remove-goods-service-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); // Check if payment methods should be shown
    });

    // Remove Goods/Service Option
    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
        reindexGoodsServices(); // Reindex remaining goods/services
        togglePaymentMethods(); // Check if payment methods should be hidden
    });

    // Reindex Pricing Options
    function reindexPricingOptions() {
        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');
                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    }

    // Reindex Goods/Services
    function reindexGoodsServices() {
        $('#goods-services-container .goods-service-option').each(function (index) {
            $(this).find('input[name^="goods_services"]').each(function () {
                const name = $(this).attr('name');
                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    }

    // Initialize payment methods visibility on page load
    togglePaymentMethods();

    // Handle payment status change
    $(document).on('change', '.change-payment-status', function () {
        const select = $(this);
        const registrationId = select.data('registration-id');
        const nonce = select.data('nonce');
        const newStatus = select.val();

        // Disable the dropdown while processing
        select.prop('disabled', true);

        // Send AJAX request to update the payment status
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_payment_status',
                registration_id: registrationId,
                payment_status: newStatus,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Payment status updated successfully.', 'success');
                } else {
                    showAdminNotice(response.data.message || 'Error updating payment status.', 'error');
                }
            },
            error: function () {
                showAdminNotice('Error updating payment status.', 'error');
            },
            complete: function () {
                // Re-enable the dropdown
                select.prop('disabled', false);
            }
        });
    });

    // Handle "View Details" button click
    $(document).on('click', '.view-registration-details', function () {
        const button = $(this);
        const registrationId = button.data('registration-id');
        const nonce = button.data('nonce');
        const modal = $('#registration-details-modal');
        const content = $('#registration-details-content');

        // Clear previous content
        content.html('<p>Loading...</p>');

        // Show the modal
        modal.show();

        // Fetch registration details via AJAX
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'view_registration_details',
                registration_id: registrationId,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<p>Error loading registration details.</p>');
                }
            },
            error: function () {
                content.html('<p>Error loading registration details.</p>');
            }
        });
    });

    // Close the modal
    // $(document).on('click', '.close-modal', function () {
    //     $(this).closest('.registration-details-modal').hide();
    // });

    // Close the modal when the "X" button is clicked
    $(document).on('click', '.close-modal', function () {
        $(this).closest('.modal').hide();
    });

    // Close the modal when the "Close" button is clicked
    $(document).on('click', '.button.close-modal', function () {
        $(this).closest('.modal').hide();
    });

    // Prevent closing the modal by clicking outside of it
    $(document).on('click', '.modal', function (e) {
        if ($(e.target).is('.modal')) {
            e.stopPropagation(); // Do nothing when clicking on the modal background
        }
    });
}); // End Ready Function
