<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    public function test_api_not_found_errors_use_standard_response_shape(): void
    {
        $this->getJson('/api/v1/missing-route')
            ->assertNotFound()
            ->assertHeader('X-Request-Id')
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
                'errors' => [],
                'code' => 'not_found',
            ]);
    }

    public function test_api_validation_errors_use_standard_response_shape(): void
    {
        $this->postJson('/api/v1/users', [])
            ->assertUnprocessable()
            ->assertHeader('X-Request-Id')
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'code' => 'validation_failed',
            ])
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonPath('errors.email.0', 'The email field is required.')
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    public function test_request_trace_id_header_is_preserved_when_supplied(): void
    {
        $this->withHeader('X-Request-Id', 'test-trace-id')
            ->get('/')
            ->assertRedirect('/admin')
            ->assertHeader('X-Request-Id', 'test-trace-id');
    }
}
