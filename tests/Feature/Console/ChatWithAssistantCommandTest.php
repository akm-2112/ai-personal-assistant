<?php

use App\Ai\Agents\PersonalAssistant;
use Illuminate\Support\Facades\File;

test('assistant chat command sends a prompt and prints the response', function () {
    PersonalAssistant::fake(['Start with your calendar priorities, then add expense tracking blocks.']);

    $this->artisan('assistant:chat', [
        'prompt' => 'Plan my day around meetings',
    ])
        ->expectsOutput('Assistant: Start with your calendar priorities, then add expense tracking blocks.')
        ->assertSuccessful();

    PersonalAssistant::assertPrompted(fn ($recordedPrompt) => str_contains(
        $recordedPrompt->prompt,
        "User request:\nPlan my day around meetings",
    ));
});

test('assistant chat command can seed a fake calendar file', function () {
    $calendarPath = storage_path('framework/testing/fake-cal.ics');

    File::delete($calendarPath);

    PersonalAssistant::fake(['Calendar ready.']);

    $this->artisan('assistant:chat', [
        'prompt' => 'Check my calendar',
        '--seed-calendar' => true,
        '--calendar-path' => $calendarPath,
    ])
        ->expectsOutput('Assistant: Calendar ready.')
        ->assertSuccessful();

    expect(File::exists($calendarPath))->toBeTrue();
    expect(File::get($calendarPath))
        ->toContain('BEGIN:VCALENDAR')
        ->toContain('SUMMARY:Daily planning and calendar review');

    File::delete($calendarPath);
});
