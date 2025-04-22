<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeAdminEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $userId;
    public $password;

    public function __construct($userId, $password)
    {
        $this->userId = $userId;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Welcome to Your Organization - Admin Credentials')
                    ->view('emails.welcome_admin');
    }
}