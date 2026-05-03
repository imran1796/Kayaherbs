<?php

return [
    'login' => [
        'max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('AUTH_LOGIN_DECAY_MINUTES', 1),
    ],

    'register' => [
        'max_attempts' => (int) env('AUTH_REGISTER_MAX_ATTEMPTS', 3),
        'decay_minutes' => (int) env('AUTH_REGISTER_DECAY_MINUTES', 1),
    ],

    'password_reset' => [
        'max_attempts' => (int) env('AUTH_PASSWORD_RESET_MAX_ATTEMPTS', 3),
        'decay_minutes' => (int) env('AUTH_PASSWORD_RESET_DECAY_MINUTES', 1),
    ],

    'session' => [
        'max_attempts' => (int) env('AUTH_SESSION_MAX_ATTEMPTS', 30),
        'decay_minutes' => (int) env('AUTH_SESSION_DECAY_MINUTES', 1),
    ],
];
