# SCT Event Administration

Contributors: Massimo Biondi\
Tags: events, events, event registration, event admin\
Requires at least: 5.0\
Tested up to: 6.3\
Requires PHP: 7.4\
Stable tag: 1.5\
License: MIT\
License URI: <https://mit-license.org/>

## Description

Event Management:

- Add, edit, and delete events with details like name, date, time, location, description, guest capacity, and admin contact information.
Store events in a custom database table.

Registration Management:

- Process and store guest registrations for events.
- Set limits for the maximum number of guests per registration.

Email Management:

- Send emails for registration confirmations and updates.
- Retry failed email notifications.

Exporting and Reporting:

- Export event registrations as CSV files for offline analysis.

Frontend Features:

- Shortcodes for displaying event lists and registration forms on the front-end.
- AJAX-powered registration handling for improved user experience.

## Usage

Shortcodes:

- [event_list] show all events
- [event_list limit="1"] show exacly one event

## Installation

Installation

- Upload the plugin folder (sct_event-administration) to the wp-content/plugins directory.
- Activate the plugin in the WordPress admin panel under "Plugins."
- Add the [event_list] shortcode to display a list of events.
- Add the [event_registration] shortcode to allow users to register for events.
- Select the page that has the [event_registration] shortcode on the Events -> Settings page
- Use the WordPress page editor to place these shortcodes on any page.

## Changelog

- 1.0
  - Initial release - 2024/11/29

- 1.1
  - get_default_confirmation_template changed to return correct eamil template

- 1.2
  - added LIMIT parameter to the shortcode "event_list" to control how many events are diaplayed in the list
  
- 1.3
  - fixed correct Email address for confiration and registration emails

- 1.4
  - another Email fix. Added 'Reply To' adress in order to satisfy the wp_mail restrictions on sending email from 3rd party domain

- 1.5
  - added reply to address instead of from addres in Emails
  - fixed display field in Edit Event dialog
  - added organizer email address to event list

- 1.6
  - adjusted permissions to include editors to be able to add, delete and modify entries

- 1.7
  - clicking on the registration count in the event list now only shows the registrations for the selected event (get_event can now retrieve not only upcoming and past events, but also specific events referenced by ID
  - placeholder fields can now be added to the message body by clicking on them in the modal email view
  - visual changes to the send email dialog iin the registrations view
  - the id before the @ mark in the email address is used as organizer reference in the event list 

