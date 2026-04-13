<?php

use App\Models\User;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => 'Modules/ExpenseTracker/database/migrations',
        '--realpath' => false,
    ]);
});

test('guests are redirected to login for expense tracker modules page', function () {
    $response = $this->get(route('expense-tracker.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit expense tracker modules page', function () {
    $user = User::factory()->make(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->get(route('expense-tracker.index'));

    $response->assertOk();
});
