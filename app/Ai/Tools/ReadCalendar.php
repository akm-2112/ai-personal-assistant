<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use om\IcalParser;
use Stringable;

class ReadCalendar implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Read the upcoming events in the calendar. ';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        if (! is_file(storage_path('app/cal.ics'))) {
            return 'Calendar file not found at storage/app/cal.ics.';
        }

        $cal = new IcalParser;
        $cal->parseFile(storage_path('app/cal.ics'));

        $events = collect($cal->getEvents()->sorted())
            ->filter(fn ($event) => $event['DTSTART'] >= now()->startOfDay()
                && $event['DTSTART'] <= now()->addDays(3)->endOfDay())
            ->map(fn ($event) => sprintf(
                '%s: %s',
                $event['DTSTART']->format('l, d M (H:i)'),
                $event['SUMMARY'],
            ));

        return $events->isEmpty()
            ? 'No upcoming events in the next 3 days'
            : $events->implode("\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
