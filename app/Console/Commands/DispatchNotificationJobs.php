<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;

class DispatchNotificationJobs extends Command
{
    /**
     * 
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to send unsent notifications';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $unsentNotifications = Notification::where('is_sent', false)->get();

        foreach ($unsentNotifications as $notification) {
            SendNotificationJob::dispatch($notification);
        }

        $this->info('Jobs dispatched for unsent notifications.');
    }
}
