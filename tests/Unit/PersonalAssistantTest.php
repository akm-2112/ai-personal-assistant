<?php

use App\Ai\Agents\PersonalAssistant;
use App\Ai\Tools\GetLocalTime;
use App\Ai\Tools\ReadCalendar;
use App\Ai\Tools\ScheduleTask;
use Tests\TestCase;

uses(TestCase::class);

test('openrouter text model can be configured for personal assistant usage', function () {
    config()->set('ai.default', 'openrouter');
    config()->set('ai.providers.openrouter.models.text.default', 'google/gemma-3-27b-it:free');

    expect(config('ai.default'))->toBe('openrouter')
        ->and(config('ai.providers.openrouter.models.text.default'))
        ->toBe('google/gemma-3-27b-it:free');
});

test('personal assistant exposes local calendar and scheduling tools', function () {
    $assistant = PersonalAssistant::make();

    $tools = collect($assistant->tools());

    expect($tools)->toHaveCount(3)
        ->and($tools->first())->toBeInstanceOf(GetLocalTime::class)
        ->and($tools->get(1))->toBeInstanceOf(ReadCalendar::class)
        ->and($tools->last())->toBeInstanceOf(ScheduleTask::class);
});
