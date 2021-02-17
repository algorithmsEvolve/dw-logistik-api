<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModel;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModel;

    public $user;

    //jadi secara default kita meminta data user
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        //kemudian emailnya meload view reset password dan passing data user
        return $this->view('emails.reset_password')->with(['user' => $this->user]);
    }
}
