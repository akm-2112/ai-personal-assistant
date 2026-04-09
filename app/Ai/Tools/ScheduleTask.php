<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ScheduleTask implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Schedule a recurring task. The task will prompt the AI with the given message at a specific time every day.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $message = trim((string) ($request['message'] ?? ''));
        $time = trim((string) ($request['time'] ?? ''));

        if ($message === '' || $time === '') {
            return 'Missing required fields. Please provide both "message" and "time".';
        }

        $taskFile = storage_path('app/scheduled-task.json');
        $tasks = File::exists($taskFile)
            ? json_decode(File::get($taskFile), true)
            : [];

        if (! is_array($tasks)) {
            $tasks = [];
        }

        $tasks[] = [
            'time' => $time,
            'message' => $message,
            'created_at' => now()->toDateTimeString(),
        ];

        File::put($taskFile, json_encode($tasks, JSON_PRETTY_PRINT));

        return 'Task scheduled successfully';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
