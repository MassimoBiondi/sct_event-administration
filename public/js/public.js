jQuery(document).ready(function($) {
    // Show registration form when register button is clicked
    $('.register-button').on('click', function() {
        var eventId = $(this).data('event-id');
        $('#event-id').val(eventId);
        $('#event-registration-form').show();
    });

    // Calculate total price dynamically
    function calculateTotalPrice() {
        let totalPrice = 0;

        $('.guest-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).siblings('input[name$="[price]"]').val()) || 0;
            totalPrice += count * price;
        });
        format = $('#total_price').data('format');
        // currency = $('#total_price').data('currency');
        $('#total_price').val(totalPrice.toFixed(format));
    }

    // Bind the calculateTotalPrice function to input changes
    $(document).on('input', '.guest-count', calculateTotalPrice);

    // Handle form submission
    $('#event-registration-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Submitting...');

        $.ajax({
            url: eventPublic.ajaxurl,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    form.html('<div class="registration-success">' +
                        '<h3>Thank you for registering!</h3>' +
                        '<p>' + response.data.message + '</p></div>');
                } else {
                    // Remove previous error highlights
                    $('#pricing-options-container, #guest_count, #email').removeClass('guest-count-error email-error');

                    // Check for guest count error
                    if (response.data && response.data.message && response.data.message.toLowerCase().includes('guest count')) {
                        // Highlight guest count section
                        $('#pricing-options-container, #guest_count').addClass('guest-count-error');
                        UIkit.notification({
                            message: '<span uk-icon="icon: warning"></span> ' + response.data.message,
                            status: 'danger',
                            pos: 'top-center',
                            timeout: 5000
                        });
                    } else if (response.data && response.data.message && response.data.message.toLowerCase().includes('duplicate entry')) {
                        // Highlight email field for duplicate registration
                        $('#email').addClass('email-error');
                        UIkit.notification({
                            message: '<span uk-icon="icon: warning"></span> Already registered with this email address.',
                            status: 'danger',
                            pos: 'top-center',
                            timeout: 5000
                        });
                    } else {
                        // General error notification
                        UIkit.notification({
                            message: '<span uk-icon="icon: warning"></span> ' + (response.data && response.data.message ? response.data.message : 'Registration failed. Please try again.'),
                            status: 'danger',
                            pos: 'top-center',
                            timeout: 5000
                        });
                    }
                    submitButton.prop('disabled', false).text(originalButtonText);
                }
            },
            error: function (xhr, status, error) {
                alert('Error submitting registration. Please try again.');
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Handle delete reservation button click
    $('.delete-reservation').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this reservation?')) {
            return;
        }

        var button = $(this);
        var reservationId = button.data('reservation-id');
        var uniqueId = button.data('unique-id');
        var nonce = button.data('nonce');

        $.ajax({
            url: eventPublic.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_reservation',
                reservation_id: reservationId,
                unique_id: uniqueId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Reservation deleted successfully.');
                    location.reload();
                } else {
                    alert('Failed to delete reservation: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while deleting reservation. Please try again.');
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Initial calculation
    calculateTotalPrice();

    // Retrieve currency settings from the HTML
    const currencySymbol = $('#total-price').data('currency-symbol') || '$';
    // const currencyFormat = parseInt($('#total-price').data('currency-format'), 10) || 2;
    const currencyFormat = $('#total-price').data('currency-format') === 0 ? 0 : (parseInt($('#total-price').data('currency-format'), 10) || 2);

    // Function to format currency
    function formatCurrency(amount) {
        return currencySymbol + amount.toFixed(currencyFormat);
    }

    // Function to update the pricing overview
    function updatePricingOverview() {
        let totalPrice = 0;
        const pricingOverviewBody = $('#pricing-overview-body');
        const pricingOverviewContainer = $('#pricing-overview');
        pricingOverviewBody.empty();

        // Process pricing options
        $('.pricing-option input[type="number"]').each(function () {
            const row = $(this).closest('.pricing-option');
            const name = row.find('input[name$="[name]"]').val();
            const price = parseFloat(row.find('input[name$="[price]"]').val()) || 0;
            const count = parseInt($(this).val(), 10) || 0;

            if (count > 0) { // Only include items with a count greater than 0
                const total = price * count;
                pricingOverviewBody.append(`
                    <tr>
                        <td>${name}</td>
                        <td style="text-align: center;">${count}</td>
                        <td style="text-align: right;">${formatCurrency(price)}</td>
                        <td style="text-align: right;">${formatCurrency(total)}</td>
                    </tr>
                `);
                totalPrice += total;
            }
        });

        // Process goods/services
        $('.goods-service-option input').each(function () {
            const row = $(this).closest('.goods-service-option');
            const name = row.find('input[name$="[name]"]').val();
            const price = parseFloat(row.find('input[name$="[price]"]').val()) || 0;
            let count = 0;

            if ($(this).is(':checkbox')) {
                // For checkboxes, count is 1 if checked, otherwise 0
                count = $(this).is(':checked') ? 1 : 0;
            } else if ($(this).is('[type="number"]')) {
                // For number inputs, parse the value
                count = parseInt($(this).val(), 10) || 0;
            }

            if (count > 0) { // Only include items with a count greater than 0
                const total = price * count;
                pricingOverviewBody.append(`
                    <tr>
                        <td>${name}</td>
                        <td style="text-align: center;">${count}</td>
                        <td style="text-align: right;">${formatCurrency(price)}</td>
                        <td style="text-align: right;">${formatCurrency(total)}</td>
                    </tr>
                `);
                totalPrice += total;
            }
        });

        // Update total price
        $('#total-price').text(formatCurrency(totalPrice));

        // Show or hide the pricing overview based on the total price
        if (totalPrice > 0) {
            pricingOverviewContainer.show();
        } else {
            pricingOverviewContainer.hide();
        }
    }

    // Bind the updatePricingOverview function to input changes
    $(document).on('input change', '.pricing-option input, .goods-service-option input', updatePricingOverview);

    // Initialize the pricing overview on page load
    updatePricingOverview();
});


