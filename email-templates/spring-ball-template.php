<?php
/**
 * Spring Ball Registration Confirmation Email Template
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
    <title>Spring Ball Registration Confirmation</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 4px solid #c0a080;
        }
        .header-title {
            font-size: 36px;
            font-weight: normal;
            margin: 0;
            letter-spacing: 2px;
            font-family: 'Georgia', serif;
        }
        .header-subtitle {
            font-size: 13px;
            letter-spacing: 3px;
            margin-top: 8px;
            opacity: 0.9;
            text-transform: uppercase;
        }
        .divider {
            border: none;
            border-top: 2px solid #c0a080;
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
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #c0a080;
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
            color: #2c3e50;
            width: 40%;
        }
        .detail-value {
            color: #555;
            text-align: right;
            width: 60%;
        }
        .special-section {
            background-color: #f9f7f4;
            padding: 20px;
            border-left: 4px solid #c0a080;
            margin: 20px 0;
        }
        .additional-info {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.8;
        }
        .additional-info-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 12px;
            text-decoration: underline;
        }
        .additional-info-item {
            margin-bottom: 15px;
        }
        .additional-info-label {
            font-weight: bold;
            color: #2c3e50;
            font-size: 13px;
        }
        .additional-info-value {
            color: #555;
            font-size: 14px;
            margin-top: 4px;
            padding-left: 12px;
            border-left: 2px solid #c0a080;
        }
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        .pricing-table th, .pricing-table td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            text-align: left;
        }
        .pricing-table th {
            background-color: #f8f8f8;
            color: #333;
            font-weight: 600;
        }
        .pricing-table .text-right {
            text-align: right;
        }
        .pricing-table .total-row {
            border-top: 2px solid #c0a080;
            font-weight: bold;
            color: #2c3e50;
        }
        .payment-box {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            padding: 18px;
            border-radius: 6px;
            margin: 20px 0;
            line-height: 1.6;
        }
        .payment-box ul {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .payment-box li {
            margin-bottom: 8px;
            color: #444;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f9f7f4;
            border-radius: 4px;
        }
        .cta-button {
            display: inline-block;
            background-color: #2c3e50;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 2px solid #c0a080;
        }
        .cta-button:hover {
            background-color: #34495e;
        }
        .footer {
            background-color: #2c3e50;
            color: #ffffff;
            text-align: center;
            padding: 20px 30px;
            font-size: 12px;
            line-height: 1.6;
            border-top: 4px solid #c0a080;
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
            color: #c0a080;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="header-title">Spring Ball</h1>
            <div class="header-subtitle">Registration Confirmation</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="salutation">
                <p>Dear {{attendee.name}},</p>
                <p>Thank you for your registration for the <strong>Spring Ball</strong>. We are delighted to welcome you to this elegant evening hosted by the Japan Swiss Society.</p>
            </div>

            <hr class="divider">

            <!-- Event Details Section -->
            <div class="section">
                <div class="section-title">Event Details</div>
                <div class="detail-row">
                    <span class="detail-label">Event</span>
                    <span class="detail-value">{{event.name}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date</span>
                    <span class="detail-value">{{event.date}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time</span>
                    <span class="detail-value">{{event.time}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location</span>
                    <span class="detail-value">{{event.location_name}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Number of Guests</span>
                    <span class="detail-value">{{attendee.guest_count}}</span>
                </div>
            </div>

            <!-- Confirmation Number -->
            <div class="special-section">
                <p style="margin: 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #666;">Registration Number</p>
                <p style="margin: 8px 0 0 0;"><span class="confirmation-number">{{registration.id}}</span></p>
            </div>

            <!-- Your Preferences Section -->
            <div class="section">
                <div class="section-title">Your Preferences</div>
                <div class="detail-row">
                    <span class="detail-label">Registration Date</span>
                    <span class="detail-value">{{registration.date}}</span>
                </div>
            </div>

            <!-- Additional Information Fields (Only show if have values) -->
            {additional_info_section}

            <!-- Pricing Overview Section -->
            {pricing_overview}

            <!-- Payment Method Details -->
            {payment_method_details}

            <hr class="divider">

            <!-- Important Information -->
            <div class="section">
                <div class="section-title">Important Information</div>
                <p style="font-size: 14px; line-height: 1.8; color: #555;">
                    Please arrive by <strong>{{event.time}}</strong> to allow time for check-in and cocktails. Formal attire is requested. We look forward to an unforgettable evening celebrating spring with the Japan Swiss Society community.
                </p>
            </div>

            <!-- Call to Action -->
            <div class="cta-section">
                <p style="margin: 0 0 15px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #666;">Manage Your Registration</p>
                <a href="{{registration.manage_link}}" class="cta-button">View Details</a>
            </div>

            <!-- Contact Information -->
            <div class="section">
                <div class="section-title">Questions?</div>
                <p style="font-size: 14px; line-height: 1.8; color: #555; margin: 0;">
                    If you have any questions regarding your reservation, please do not hesitate to contact us. We are here to assist you.
                </p>
            </div>

            <div class="note">
                <p style="margin: 0;">This is an automated email. Please do not reply directly to this address. For assistance, please visit our event management page using the link above.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0;">{{website.name}}</p>
            <hr class="footer-divider">
            <p style="margin: 0; opacity: 0.9;">
                {{website.url}}<br>
                © {{date.year}} Japan Swiss Society. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>