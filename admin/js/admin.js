jQuery(document).ready(function($) {
    // Function to display admin notices
    function showAdminNotice(message, type) {
        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p>');
        $('.wp-header-end').after(notice);
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Handle event form submission
    $('#add-event-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    window.location.href = 'admin.php?page=event-admin';
                } else {
                    alert('Error: ' + response.data.message);
                }
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
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
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
        document.execCommand('copy');
        tempInput.remove();
        
        // Show a success message
        showAdminNotice('URL <strong>'+ url +'</strong> copied to clipboard: ', 'success');
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
                if ($emailBody.length != 0) {
                    insertPlaceholder($emailBody);
                } else if ($customEmailTemplate.length != 0) {
                    insertPlaceholder($customEmailTemplate);
                }
            });
    });
    // $('.placeholder-info code').each(function() {
    //     $(this)
    //         .css('cursor', 'pointer')
    //         .attr('title', 'Click to insert')
    //         .on('click', function() {
    //             var $emailBody = $('#email_body');
    //             var placeholder = $(this).text() + ' ';
                
    //             // Get cursor position
    //             var startPos = $emailBody[0].selectionStart;
    //             var endPos = $emailBody[0].selectionEnd;
                
    //             // Insert placeholder at cursor position
    //             var currentContent = $emailBody.val();
    //             var newContent = currentContent.substring(0, startPos) + 
    //                              placeholder + 
    //                              currentContent.substring(endPos);
    //             $emailBody.val(newContent);
                
    //             // Move cursor after the inserted placeholder
    //             var newCursorPos = startPos + placeholder.length;
    //             $emailBody[0].setSelectionRange(newCursorPos, newCursorPos);
                
    //             // Focus back on the textarea
    //             $emailBody.focus();
    //         });
    // });
    
    $('.guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var updateButton = input.siblings('.update-guest-count');

        // Reset all other guest-count inputs except this one
        $('.guest-count').not(this).each(function() {
            var otherInput = $(this);
            otherInput.val(otherInput.data('original'));
            otherInput.siblings('.update-guest-count').hide();
        });
        
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    // Handle member guest count updates
    $('.member-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var updateButton = input.siblings('.update-member-guest-count');

        // Reset all other member guest count inputs except this one
        $('.member-guest-count').not(this).each(function() {
            var otherInput = $(this);
            otherInput.val(otherInput.data('original'));
            otherInput.siblings('.update-member-guest-count').hide();
        });
        
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
        $('.non-member-guest-count').not(this).each(function() {
            var otherInput = $(this);
            otherInput.val(otherInput.data('original'));
            otherInput.siblings('.update-non-member-guest-count').hide();
        });
        
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    // Handle registration deletion
    $('.delete-registration').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this registration?')) {
            return;
        }

        var button = $(this);
        var registrationId = button.data('registration-id');
        var nonce = button.data('nonce');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_registration',
                registration_id: registrationId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Error deleting registration');
                }
            },
            error: function() {
                alert('Error deleting registration');
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
         var name = row.find('td:first').text();
         
         $('#recipient_email').val(recipientEmail);
         $('#registration_id').val(registrationId);
         $('#is_mass_email').val('0');

         // Fill recipient Name in Modal
         $('#admin_mail_recipient').text(name);

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

        $('#admin_mail_recipient').text('All');

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
            //  closeModal();
         }
     });
 
     // Prevent modal from closing when clicking inside the modal content
     $('.email-modal-content').on('click', function(e) {
         e.stopPropagation();
     });
     
     // Function to close modal
     function closeModal() {
        modal.hide();
        if ($('#send-email-form').length) {
            $('#send-email-form')[0].reset();
            // Clear all hidden fields too
            $('#recipient_email').val('');
            $('#registration_id').val('');
            $('#event_id').val('');
            $('#is_mass_email').val('');    
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
                    alert(response.data.message);
                    closeModal();
                    // modal.hide();
                    // form[0].reset();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error sending email: ' + error);
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });
    
    // Handle retry email button click
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
                    // Show success message
                    alert('Email sent successfully!');
                    // Optionally refresh the page or update UI
                    location.reload();
                } else {
                    alert('Failed to send email: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while sending email. Please try again.');
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
        const content = $(this).data('content');
        
        $('#modal-email-subject').text(subject);
        $('#email-content').text(content);
        $('#email-content-modal').show();
    });

    // View recipients handler
    $('.view-recipients').click(function() {
        const recipientsData = $(this).data('recipients').split('|');
        const subject = $(this).data('subject');
        const eventId = $(this).data('event-id');
        const wp_nonce = $(this).data('nonce'); // Make sure this is set in your PHP
        
        $('#modal-recipients-title').text('Recipients for: ' + subject);
        
        let recipientsList = '<ul>';
        recipientsData.forEach(function(recipientData) {
            const [email, status] = recipientData.split(':');
            const statusClass = status === 'failed' ? 'failed-status' : 'success-status';
            const statusIcon = status === 'failed' ? 
                '<span class="dashicons dashicons-warning"></span>': 
                '<span class="dashicons dashicons-yes-alt"></span>';
            
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

    // Close recipients modal
    $(document).on('click', '.close-modal, .modal', function(event) {
        if (event.target === this) {
            $('#recipients-modal, #email-content-modal, #event-details-modal').hide();
        }
    });

    // Helper function to show notices in the modal
    function showNotice(message, type) {
        const noticeHtml = `
            <div class="notice notice-${type} is-dismissible inline-notice">
                <p>${message}</p>
            </div>`;
        
        $('.modal-notice').remove(); // Remove any existing notices
        $('#recipients-list').before(noticeHtml);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $('.modal-notice').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Retry single email handler
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
                    listItem.find('.dashicons-warning').removeClass('dashicons-warning').addClass('dashicons-yes-alt');
                    listItem.find('.status-label').text('Sent');
                    button.remove();

                    // Show success message
                    showNotice('Email resent successfully', 'success');
                } else {
                    button.prop('disabled', false)
                        .find('.dashicons-update')
                        .removeClass('spinning');
                    showNotice(response.data.message || 'Failed to resend email', 'error');
                }
            },
            error: function() {
                button.prop('disabled', false)
                    .find('.dashicons-update')
                    .removeClass('spinning');
                showNotice('Server error occurred', 'error');
            }
        });
    });

    function calculateTotals(table) {
        var totalPrice = 0;
        var totalGuests = 0;
        var totalMembers = 0;
        var totalNonMembers = 0;
        var totalChildren = 0;
        var currencyFormat = parseInt($('#currency-format').data('currency-format'));

        table.find('.total-price-cell .total-price').each(function() {
            var price = parseFloat($(this).text().replace(/[^0-9.-]+/g, ""));
            if (!isNaN(price)) {
                totalPrice += price;
            }
        });

        table.find('.member-guest-count').each(function() {
            var memberGuests = parseInt($(this).val(), 10);
            if (!isNaN(memberGuests)) {
                totalMembers += memberGuests;
            }
        });

        table.find('.non-member-guest-count').each(function() {
            var nonMemberGuests = parseInt($(this).val(), 10);
            if (!isNaN(nonMemberGuests)) {
                totalNonMembers += nonMemberGuests;
            }
        });

        table.find('.children-guest-count').each(function() {
            var childrenGuests = parseInt($(this).val(), 10);
            if (!isNaN(childrenGuests)) {
                totalChildren += childrenGuests;
            }
        });

        totalGuests = totalMembers + totalNonMembers + totalChildren;

        table.find('.table-total-price .total-price-value').text(totalPrice.toFixed(currencyFormat));
        table.find('.total-guests').text(totalGuests);
        table.find('.total-members').text(totalMembers);
        table.find('.total-non-members').text(totalNonMembers);
        table.find('.total-children').text(totalChildren);

        var eventCapacity = parseInt(table.data('event-capacity'), 10);
        var remainingCapacity = eventCapacity - totalGuests;
        if (eventCapacity === 0) {
            remainingCapacity = '';
        } else if (remainingCapacity === 0) {
            remainingCapacity = 'Sold Out';
            table.find('.remaining-capacity').addClass('status-indicator status-failed');
        } else if (remainingCapacity < 0) { 
            remainingCapacity = 'Over Capacity';
        } else {
            remainingCapacity = remainingCapacity + ' Remaining';
        }
        table.find('.remaining-capacity').text(remainingCapacity);
    }

    // Initial calculation for all tables
    $('.wp-list-table').each(function() {
        calculateTotals($(this));
    });

    // Recalculate totals when a guest count or total-price-cell changes value
    // $(document).on('input', '.member-guest-count, .non-member-guest-count, .children-guest-count, .total-price-cell .total-price', function() {
    //     var table = $(this).closest('.wp-list-table');
    //     calculateTotals(table);
    // });

    // Initialize DataTables on specific pages
    var currentPage = $('body').attr('class').match(/page-(event-admin|event-registrations|event-past)/);
    if (currentPage) {
        $('.wp-list-table').DataTable({
            "order": [], // Disable initial sorting
            "columnDefs": [
                { "orderable": false, "targets": -1 }, // Disable sorting on the last column (Actions)
                { "orderDataType": "dom-text", "targets": [2, 3] } // Apply custom sorting to columns with input fields
            ]
        });

        // Custom sorting for input fields
        $.fn.dataTable.ext.order['dom-text'] = function(settings, col) {
            return this.api().column(col, { order: 'index' }).nodes().map(function(td, i) {
                return parseFloat($('input', td).val()) || 0;
            });
        };
    }

    // Handle children guest count update
    $('.children-guest-count').on('change', function() {
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


    // Initialize DataTables on registration tables
    
    $('.wp-list-table').DataTable({
        order: [], // Disable initial sorting
        // "paging": false,
        lengthMenu: (function() {
            var defaultLengths = [5, 10, 20, -1];
            var displayLengths = [5, 10, 20, "All"];
            if (eventAdmin.items_per_page && !defaultLengths.includes(eventAdmin.items_per_page)) {
                defaultLengths.splice(defaultLengths.length - 1, 0, eventAdmin.items_per_page);
                displayLengths.splice(displayLengths.length - 1, 0, eventAdmin.items_per_page);
            }
            // Combine, sort by display, and separate the arrays
            var combined = defaultLengths.map(function(length, index) {
                return { length: length, display: displayLengths[index] };
            });
            combined.sort(function(a, b) {
                if (a.display === "All") return 1;
                if (b.display === "All") return -1;
                return a.display - b.display;
});
            defaultLengths = combined.map(function(item) { return item.length; });
            displayLengths = combined.map(function(item) { return item.display; });    
            return [defaultLengths, displayLengths];
        })(),
        pageLength: eventAdmin.items_per_page,
        columnDefs: [
            { "orderable": false, "targets": -1 }, // Disable sorting on the last column (Actions)
            { "orderDataType": "dom-text", "targets": [2, 3] } // Apply custom sorting to columns with input fields
        ],
        initComplete: function() {
            var table = this.api();
            var registrationDateColumnIndex = table.column('th:contains("Registration Date")').index();
            table.order([registrationDateColumnIndex, 'asc']).draw();
        }
    });

    // Custom sorting for input fields
    $.fn.dataTable.ext.order['dom-text'] = function(settings, col) {
        return this.api().column(col, { order: 'index' }).nodes().map(function(td, i) {
            return parseFloat($('input', td).val()) || 0;
        });
    };


        // Consolidated function to handle guest count updates
    function handleGuestCountUpdate(button, guestType) {
        var input = button.siblings('.' + guestType + '-guest-count');
        var registrationId = button.closest('tr').data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);
        var memberGuestCount = parseInt(button.closest('tr').find('.member-guest-count').val(), 10) || 0;
        var nonMemberGuestCount = parseInt(button.closest('tr').find('.non-member-guest-count').val(), 10) || 0;
        var childrenGuestCount = parseInt(button.closest('tr').find('.children-guest-count').val(), 10) || 0;
        var memberPrice = parseFloat(button.closest('tr').data('member-price')) || 0;
        var nonMemberPrice = parseFloat(button.closest('tr').data('non-member-price')) || 0;
        var eventCapacity = parseInt(button.closest('table').data('event-capacity'), 10);
        // var totalRegistered = parseInt(button.closest('table').data('total-registered'), 10);
        // var totalGuests = parseInt(button.closest('table').data('total-guests'), 10);
        // var totalMembers = parseInt(button.closest('table').data('total-members'), 10);
        var currencyFormat = parseInt($('#currency-format').data('currency-format'));
    
        var data = {
            action: 'update_registration',
            registration_id: registrationId,
            member_guests: memberGuestCount,
            non_member_guests: nonMemberGuestCount,
            children_guests: childrenGuestCount,
            security: eventAdmin.update_registration_nonce // Include the nonce
        };
    
        // Update the specific guest count in the data object
        data[guestType + '_guests'] = newGuestCount;
    
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                button.prop('disabled', true).text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount);
                    button.hide();
                    // Update total guests, total members, and total price
                    var totalGuests = memberGuestCount + nonMemberGuestCount + childrenGuestCount;
                    var totalPrice = (memberPrice * memberGuestCount) + (nonMemberPrice * nonMemberGuestCount);
                    button.closest('table').find('.total-guests').text(totalGuests);
                    button.closest('tr').find('.total-price').text(totalPrice.toFixed(currencyFormat));
                    // Update remaining capacity
                    var remainingCapacity = eventCapacity - totalGuests;
                    button.closest('table').find('.remaining-capacity').text(remainingCapacity);
                    // Update total guests and total members attributes
                    button.closest('table').data('total-guests', totalGuests);
                    button.closest('table').data('total-members', memberGuestCount);
                    var totalPrice = 0;
                    button.closest('table').find('.total-price-cell .total-price').each(function() {
                        var price = parseFloat($(this).text().replace(/[^0-9.-]+/g, ""));
                        if (!isNaN(price)) {
                            totalPrice += price;
                        }
                    });
                    button.closest('table').find('.table-total-price .total-price-value').text(totalPrice.toFixed(currencyFormat));
    
                    // Show success message
                    var successMessage = $('<span class="success-message" style="color: green; margin-left: 10px;">Updated!</span>');
                    button.after(successMessage);
                    setTimeout(function() {
                        successMessage.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 2000);
    
                    // Recalculate totals
                    calculateTotals(button.closest('table'));
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    input.val(input.data('original'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Error updating guest count: ' + error);
                input.val(input.data('original'));
            },
            complete: function() {
                button.prop('disabled', false).text('Update');
            }
        });
    }
    
    // Handle guest count update button clicks
    $('.update-member-guest-count, .update-non-member-guest-count, .update-children-guest-count').on('click', function() {
        var button = $(this);
        var guestType = button.hasClass('update-member-guest-count') ? 'member' :
                        button.hasClass('update-non-member-guest-count') ? 'non-member' : 'children';
        handleGuestCountUpdate(button, guestType);
    });

    // Open Add Registration Modal
    $('.add-registration').on('click', function() {
        var eventId = $(this).data('event-id');
        var childrenCountedSeparately = $(this).data('children-counted-separately');
        var memberOnly = $(this).data('member-only');
        var memberPrice = $(this).data('member-price');
        var nonMemberPrice = $(this).data('non-member-price');
        
        $('#add_registration_event_id').val(eventId);
        
        // Show or hide the children guests field based on the event's property
        if (childrenCountedSeparately) {
            $('#registration_children_guests_field').show();
        } else {
            $('#registration_children_guests_field').hide();
        }
        
        // Show or hide the member guests field based on the event's property
        if (memberOnly || memberPrice > 0) {
            $('#registration_member_guests_field').show();
        } else {
            $('#registration_member_guests_field').hide();
        }
        
        // Show or hide the non-member guests field based on the event's property
        if (nonMemberPrice > 0) {
            $('#registration_non_member_guests_field').show();
        } else {
            $('#registration_non_member_guests_field').hide();
        }
        
        $('#add-registration-modal').show();
    });

    // Handle Add Registration Form Submission
    $('#add-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();
        
        submitButton.prop('disabled', true).text('Adding...');
        
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Registration added successfully', 'success');
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Error adding registration: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Close modal
    $('.close-modal').on('click', function() {
        $(this).closest('.modal').hide();
    });

    // Close modal when clicking outside of the modal content
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $(e.target).hide();
        }
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
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Winners selected successfully', 'success');
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + response.data, 'error');
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

    // Close modal
    $('.close-modal, .cancel-selection').on('click', function() {
        $(this).closest('.email-modal').hide();
    });

    // Handle export custom tables button click
    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce
            },
            success: function(response) {
                if (response.success) {
                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = 'custom_tables_export.sql';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                } else {
                    alert('Failed to export tables: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while exporting tables. Please try again.');
                console.error('AJAX Error:', status, error);
            }
        });
    });

}); // End Ready Function
