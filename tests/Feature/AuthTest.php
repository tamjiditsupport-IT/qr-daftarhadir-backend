<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data' => ['token', 'user']]);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }
    
    public function test_login_is_rate_limited()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Route login is throttled 5,1 (5 times per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email_or_username' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }
}
