<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewUser extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $name, $password)
    {
        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $homeUrl = url('/');
        $changePasswordUrl = url(route('password.request'));
        return $this->markdown('emails.new_user')
                    ->with([
                        'homeUrl' => $homeUrl,
                        'changePasswordUrl' => $changePasswordUrl,
                        'email' => $this->email,
                        'name' => $this->name,
                        'password' => $this->password,
                    ]);
    }
}
