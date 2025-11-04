<?php
/**
 * Outdoor Christmas Registration Confirmation Email Template
 * 
 * This template is processed by the email system and receives $data array with all placeholders
 * 
 * Available variables:
 * - attendee: array with name, email, guest_count
 * - event: array with name, date, time, location_name
 * - registration: array with id, date, manage_link
 * - additional: array with additional fields
 * - payment: array with pricing_overview, method_details, total
 * - website: array with name, url
 * - date: array with current, year
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outdoor Christmas Registration Confirmation</title>
    <style>
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #b71c1c 0%, #8b0000 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 4px solid #2d5016;
            position: relative;
        }
        .snowflake {
            position: absolute;
            font-size: 24px;
            opacity: 0.6;
        }
        .snowflake-left {
            left: 20px;
            top: 15px;
        }
        .snowflake-right {
            right: 20px;
            top: 15px;
        }
        .header-title {
            font-size: 36px;
            font-weight: normal;
            margin: 0;
            letter-spacing: 2px;
            font-family: 'Georgia', serif;
            position: relative;
            z-index: 1;
        }
        .header-subtitle {
            font-size: 13px;
            letter-spacing: 3px;
            margin-top: 8px;
            opacity: 0.9;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }
        .divider {
            border: none;
            border-top: 2px solid #2d5016;
            margin: 30px 0;
            opacity: 0.5;
        }
        .content {
            padding: 40px 30px;
        }
        .salutation {
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #b71c1c;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2d5016;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e5e5;
            font-size: 14px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
            width: 40%;
        }
        .detail-value {
            color: #555;
            text-align: right;
            width: 60%;
            word-break: break-word;
        }
        .special-section {
            background-color: #fef8f0;
            padding: 20px;
            border-left: 4px solid #2d5016;
            margin: 20px 0;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
        .cta-button {
            display: inline-block;
            background-color: #b71c1c;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 2px solid #2d5016;
        }
        .cta-button:hover {
            background-color: #8b0000;
        }
        .footer {
            background-color: #2d5016;
            color: #ffffff;
            text-align: center;
            padding: 20px 30px;
            font-size: 12px;
            line-height: 1.6;
            border-top: 4px solid #b71c1c;
        }
        .footer-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 12px 0;
        }
        .note {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e5e5;
        }
        .confirmation-number {
            font-size: 20px;
            font-weight: bold;
            color: #b71c1c;
            letter-spacing: 2px;
        }
        .festive-icon {
            font-size: 20px;
            margin-right: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table thead tr {
            background-color: #f0f0f0;
        }
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 13px;
        }
        table th {
            font-weight: bold;
            color: #333;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="snowflake snowflake-left">❄️</div>
            <div class="snowflake snowflake-right">❄️</div>
            <h1 class="header-title"><span class="festive-icon">🎄</span>Outdoor Christmas<span class="festive-icon">🎄</span></h1>
            <div class="header-subtitle">Registration Confirmation</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="salutation">
                <p>Dear <?php echo isset($data['attendee']['name']) ? esc_html($data['attendee']['name']) : 'Valued Guest'; ?>,</p>
                <p>Thank you for your registration for <strong>Outdoor Christmas at the Swiss Ambassador's Residence</strong>. We are delighted to welcome you to this festive celebration hosted by the Swiss Club Tokyo.</p>
            </div>

            <hr class="divider">

            <!-- Event Details Section -->
            <div class="section">
                <div class="section-title"><span class="festive-icon">📍</span>Event Details</div>
                <div class="detail-row">
                    <span class="detail-label">Event</span>
                    <span class="detail-value"><?php echo isset($data['event']['name']) ? esc_html($data['event']['name']) : 'Outdoor Christmas'; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date</span>
                    <span class="detail-value"><?php echo isset($data['event']['date']) ? esc_html($data['event']['date']) : ''; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time</span>
                    <span class="detail-value"><?php echo isset($data['event']['time']) ? esc_html($data['event']['time']) : ''; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location</span>
                    <span class="detail-value"><?php echo isset($data['event']['location_name']) ? esc_html($data['event']['location_name']) : ''; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Number of Guests</span>
                    <span class="detail-value"><?php echo isset($data['attendee']['guest_count']) ? intval($data['attendee']['guest_count']) : 0; ?></span>
                </div>
            </div>

            <!-- Confirmation Number -->
            <div class="special-section">
                <p style="margin: 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #666;">Registration Number</p>
                <p style="margin: 8px 0 0 0;"><span class="confirmation-number"><?php echo isset($data['registration']['id']) ? esc_html($data['registration']['id']) : ''; ?></span></p>
            </div>

            <!-- Your Registration Section -->
            <div class="section">
                <div class="section-title"><span class="festive-icon">✓</span>Your Registration</div>
                <div class="detail-row">
                    <span class="detail-label">Registration Date</span>
                    <span class="detail-value"><?php echo isset($data['registration']['date']) ? esc_html($data['registration']['date']) : ''; ?></span>
                </div>
                <div class="detail-row" style="border-bottom: none;">
                    <span class="detail-label">Your Name</span>
                    <span class="detail-value"><?php echo isset($data['attendee']['name']) ? esc_html($data['attendee']['name']) : ''; ?></span>
                </div>
            </div>

            <!-- Additional Fields Section (if provided) -->
            <?php if (!empty($data['additional']) && is_array($data['additional'])): ?>
            <div class="section">
                <div class="section-title">Your Preferences</div>
                <?php foreach ($data['additional'] as $field): ?>
                    <div class="detail-row">
                        <span class="detail-label"><?php echo esc_html($field['label'] ?? ''); ?></span>
                        <span class="detail-value"><?php echo esc_html($field['value'] ?? ''); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Pricing Overview -->
            <?php if (!empty($data['payment']['pricing_overview'])): ?>
                <?php echo wp_kses_post($data['payment']['pricing_overview']); ?>
            <?php endif; ?>

            <!-- Payment Method Details -->
            <?php if (!empty($data['payment']['method_details'])): ?>
                <div class="section">
                    <div class="section-title">Payment Information</div>
                    <?php echo wp_kses_post($data['payment']['method_details']); ?>
                </div>
            <?php endif; ?>

            <hr class="divider">

            <!-- Important Information -->
            <div class="section">
                <div class="section-title"><span class="festive-icon">❄️</span>Important Information</div>
                <p style="font-size: 14px; line-height: 1.8; color: #555;">
                    Please arrive by <strong><?php echo isset($data['event']['time']) ? esc_html($data['event']['time']) : ''; ?></strong> to join us for this festive outdoor celebration. Dress warmly and enjoy seasonal refreshments, festive music, and the magical atmosphere of an outdoor Christmas celebration at the Swiss Ambassador's Residence.
                </p>
                <p style="font-size: 14px; line-height: 1.8; color: #555; margin-top: 12px;">
                    We look forward to celebrating the Christmas season with you and the Swiss Club Tokyo community!
                </p>
            </div>

            <!-- Call to Action -->
            <div class="cta-section">
                <p style="margin: 0 0 15px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #666;">Manage Your Registration</p>
                <a href="<?php echo isset($data['registration']['manage_link']) ? esc_url($data['registration']['manage_link']) : '#'; ?>" class="cta-button">View Details</a>
            </div>

            <!-- Contact Information -->
            <div class="section">
                <div class="section-title">Questions?</div>
                <p style="font-size: 14px; line-height: 1.8; color: #555; margin: 0;">
                    If you have any questions regarding your registration, please do not hesitate to contact us. We are here to assist you and ensure you have a wonderful experience at our event.
                </p>
            </div>

            <div class="note">
                <p style="margin: 0;">This is an automated email. Please do not reply directly to this address. For assistance, please visit our event management page using the link above.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0;"><?php echo isset($data['website']['name']) ? esc_html($data['website']['name']) : 'Swiss Club Tokyo'; ?></p>
            <hr class="footer-divider">
            <p style="margin: 0; opacity: 0.9;">
                <?php echo isset($data['website']['url']) ? esc_html($data['website']['url']) : ''; ?><br>
                © <?php echo isset($data['date']['year']) ? intval($data['date']['year']) : date('Y'); ?> Swiss Club Tokyo. All rights reserved.
            </p>
            <p style="margin: 10px 0 0 0; opacity: 0.8;"><span class="festive-icon">🎄</span>Wishing you a Merry Christmas and Happy Holidays!<span class="festive-icon">🎄</span></p>
        </div>
    </div>
</body>
</html>
