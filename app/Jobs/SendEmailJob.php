<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Mail;
use App\Mail\Correos\CorreoParticipacion;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    protected $email, $emailType;
    /**
     * Create a new job instance.
     */
    public function __construct($email, $emailType)
    {
        //
        $this->email = $email;
        $this->emailType = $emailType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new CorreoParticipacion());

    }
}
