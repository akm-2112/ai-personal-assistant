<?php

use App\Models\User;

test('guests are redirected to the login page from chat', function () {
    $response = $this->get(route('chat'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit chat page', function () {
    $user = User::factory()->make();
    $this->actingAs($user);

    $response = $this->get(route('chat'));

    $response->assertOk();
});
