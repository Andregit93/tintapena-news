<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $connection = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION');
        $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');

        if ($connection === 'mysql' && $database !== 'tintapena_test') {
            throw new \RuntimeException('Refusing to run MySQL tests outside tintapena_test.');
        }

        parent::setUp();
    }
}
