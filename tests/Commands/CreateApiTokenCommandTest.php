<?php

namespace Tests\Commands;

use BookStack\Users\Models\User;
use Tests\TestCase;

class CreateApiTokenCommandTest extends TestCase
{
    public function test_command_creates_token_for_user()
    {
        $user = User::factory()->create();

        $this->artisan("bookstack:create-api-token --id={$user->id} --name=TestToken")
            ->expectsOutputToContain('Token ID:')
            ->expectsOutputToContain('Secret:')
            ->assertExitCode(0);

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'TestToken',
        ]);
    }
}
