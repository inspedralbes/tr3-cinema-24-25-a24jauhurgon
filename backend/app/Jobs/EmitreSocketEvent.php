<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmitreSocketEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public array $backoff = [1, 3];
    public $timeout = 10;

    protected string $event;
    protected array $payload;
    protected string $room;

    /**
     * Create a new job instance.
     */
    public function __construct(string $event, array $payload, string $room = '')
    {
        $this->event = $event;
        $this->payload = $payload;
        $this->room = $room;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::timeout(5)->post('http://socket:3002/emit', [
            'event' => $this->event,
            'payload' => $this->payload,
            'room' => $this->room,
        ]);

        $response->throw();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Failed to emit socket event '{$this->event}'", [
            'event' => $this->event,
            'room' => $this->room,
            'error' => $exception->getMessage()
        ]);
    }
}
