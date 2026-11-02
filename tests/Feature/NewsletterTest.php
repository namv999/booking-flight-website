<?php

namespace Tests\Feature;

use Tests\TestCase;

class NewsletterTest extends TestCase
{
    public function test_subscribe_success_with_valid_email(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_subscribe_fails_with_invalid_email(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
