<?php

use Illuminate\Support\Facades\Mail;

if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body)
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject);
            });
            return true; // email sent successfully
        } catch (\Exception $e) {
            return $e->getMessage(); // return error message
        }
    }
}
