<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Farmer;
use App\Models\AuthorizedDealer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notification = $this->notification;

        // Get recipients based on recipient type
        if ($notification->recipient_type === 'all') {
            $farmers = Farmer::all();
            $dealers = AuthorizedDealer::all();
            $recipients = $farmers->merge($dealers);
        } elseif ($notification->recipient_type === 'farmers') {
            $recipients = Farmer::all();
        } elseif ($notification->recipient_type === 'authorized_dealers') {
            $recipients = AuthorizedDealer::all();
        }

        foreach ($recipients as $recipient) {
            // Example logic for sending email or SMS
            // Mail::to($recipient->email)->send(new NotificationMail($notification->message));
        }

        // Mark the notification as sent
        $notification->update(['is_sent' => true]);
    }
}
