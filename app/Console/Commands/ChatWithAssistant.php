<?php

namespace App\Console\Commands;

use App\Ai\Agents\PersonalAssistant;
use App\Ai\Tools\ReadCalendar;
use DateTimeInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Signature('assistant:chat
    {prompt? : One-off prompt to send to the assistant}
    {--seed-calendar : Create a fake calendar file if missing}
    {--calendar-path= : Calendar .ics path (defaults to storage/app/cal.ics)}
')]
#[Description('Chat with the PersonalAssistant agent from the terminal')]
class ChatWithAssistant extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('seed-calendar')) {
            $this->seedCalendarFile();
        }

        $prompt = $this->argument('prompt');

        if (is_string($prompt) && trim($prompt) !== '') {
            return $this->sendPrompt(trim($prompt));
        }

        $this->components->info('Chat started. Type "exit" to quit.');

        while (true) {
            $input = $this->ask('You');

            if (! is_string($input)) {
                return self::SUCCESS;
            }

            $input = trim($input);

            if ($input === '') {
                continue;
            }

            if (in_array(strtolower($input), ['exit', 'quit', 'q'], true)) {
                $this->components->info('Session ended.');

                return self::SUCCESS;
            }

            if ($this->sendPrompt($input) === self::FAILURE) {
                return self::FAILURE;
            }
        }
    }

    /**
     * Send a prompt to the assistant and print the response.
     */
    private function sendPrompt(string $prompt): int
    {
        $calendarSummary = (new ReadCalendar)->handle(new Request(['days_ahead' => 7]));
        $contextPrompt = implode("\n\n", [
            'Current local time (Asia/Yangon): '.now()->timezone('Asia/Yangon')->format('Y-m-d H:i:s'),
            "Upcoming calendar events (from storage/app/cal.ics):\n{$calendarSummary}",
            "User request:\n{$prompt}",
        ]);

        try {
            $response = PersonalAssistant::make()->prompt($contextPrompt);
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error(
                'Assistant request failed. Check your AI provider credentials and network configuration.',
            );
            $this->line(Str::limit($exception->getMessage(), 300));

            return self::FAILURE;
        }

        $this->line(sprintf('Assistant: %s', $response->text));

        return self::SUCCESS;
    }

    /**
     * Seed a fake iCalendar file for local testing.
     */
    private function seedCalendarFile(): void
    {
        $calendarPath = $this->calendarPath();

        if (File::exists($calendarPath)) {
            $this->components->warn("Calendar file already exists at {$calendarPath}. Skipping seed.");

            return;
        }

        File::ensureDirectoryExists(dirname($calendarPath));

        $calendarContent = implode("\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Personal AI Assistant//Fake Calendar//EN',
            'CALSCALE:GREGORIAN',
            ...$this->eventBlock(
                uid: 'task-1@personal-ai',
                start: now()->addDay()->setTime(9, 0),
                end: now()->addDay()->setTime(9, 45),
                summary: 'Daily planning and calendar review',
            ),
            ...$this->eventBlock(
                uid: 'task-2@personal-ai',
                start: now()->addDay()->setTime(14, 0),
                end: now()->addDay()->setTime(15, 0),
                summary: 'Client follow-up task block',
            ),
            ...$this->eventBlock(
                uid: 'task-3@personal-ai',
                start: now()->addDays(2)->setTime(18, 30),
                end: now()->addDays(2)->setTime(19, 0),
                summary: 'Expense tracker planning session',
            ),
            'END:VCALENDAR',
            '',
        ]);

        File::put($calendarPath, $calendarContent);

        $this->components->info("Seeded fake calendar at {$calendarPath}.");
    }

    /**
     * Get the target calendar path for seeding.
     */
    private function calendarPath(): string
    {
        $calendarPathOption = $this->option('calendar-path');

        if (is_string($calendarPathOption) && trim($calendarPathOption) !== '') {
            return str_starts_with($calendarPathOption, '/')
                ? $calendarPathOption
                : base_path($calendarPathOption);
        }

        return storage_path('app/cal.ics');
    }

    /**
     * Build a single VEVENT block for an iCalendar document.
     *
     * @return string[]
     */
    private function eventBlock(
        string $uid,
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $summary,
    ): array {
        $timestamp = now()->format('Ymd\THis');

        return [
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$timestamp}",
            'DTSTART:'.$start->format('Ymd\THis'),
            'DTEND:'.$end->format('Ymd\THis'),
            "SUMMARY:{$summary}",
            'END:VEVENT',
        ];
    }
}
