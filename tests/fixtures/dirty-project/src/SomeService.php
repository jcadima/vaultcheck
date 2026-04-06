<?php

// Fixture file for codebase scanning tests (C001-C004)
// Intentionally calls env() outside config/ to trigger C004
// References some undefined vars to trigger C002
// Calls env() without defaults to trigger C003

class SomeService
{
    public function connect(): void
    {
        // C004: env() outside config/ — Laravel anti-pattern
        $host = env('DB_HOST');              // no default → C003
        $port = env('DB_PORT', 3306);        // has default — OK
        $pass = env('DB_PASSWORD');          // no default → C003

        // C002: referenced but not in .env
        $apiKey = env('UNDEFINED_API_KEY');  // not in .env → C002
    }

    public function mail(): void
    {
        // C004: outside config/
        $mailer = env('MAIL_MAILER', 'smtp');
    }
}
