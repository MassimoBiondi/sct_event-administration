<?php
class EmailHandler {
    public function send_registration_notification($event, $name, $email, $guests) {
        $to = $event->admin_email;
        $subject = "New Registration for {$event->event_name}";
        
        $message = "New registration details:\n\n";
        $message .= "Event: {$event->event_name}\n";
        $message .= "Date: " . date('F j, Y', strtotime($event->event_date)) . "\n";
        $message .= "Registrant Name: {$name}\n";
        $message .= "Registrant Email: {$email}\n";
        $message .= "Number of Guests: {$guests}\n";
        
        wp_mail($to, $subject, $message);
    }
}
