<?php
 
namespace App\Jobs;
 
use Illuminate\Bus\Queueable;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
 
class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $fcmToken;
    protected $title;
    protected $description;
protected $data;
    public function __construct($fcmToken,$title,$description, $data)
    {
        $this->fcmToken = $fcmToken;
        $this->title = $title;
        $this->description = $description;
        $this->data = $data;
    }
 
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            NotificationHelper::sendFcmNotification($this->fcmToken, $this->title, $this->description, $this->data);
        } catch (\Exception $exception) {
            // Handle exceptions like logging the failure
            Log::error('FCM send error: ' . $exception->getMessage());
        }
    }
}
 
 
//jobs
 