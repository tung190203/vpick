<?php

namespace App\Jobs;

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;
    protected string $title;
    protected string $body;
    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ) {
        $this->userId = $userId;
        $this->title  = $title;
        $this->body   = $body;
        $this->data   = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebase): void
    {
        $firebase->sendToUser(
            $this->userId,
            $this->title,
            $this->body,
            $this->data
        );
    }
}
