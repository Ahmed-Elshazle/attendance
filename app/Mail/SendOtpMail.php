<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;
    public $otpCode;
    public $userName;
    public function __construct($otpCode, $userName)
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('الرقم السرى المتغير')
                    ->view('send_otp')
                    ->with([
                        'otpCode' => $this->otpCode,
                        'userName' => $this->userName,
                    ]);
    }

}
