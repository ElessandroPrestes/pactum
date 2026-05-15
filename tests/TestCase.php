<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public function postJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        $headers['Idempotency-Key'] ??= (string) Str::uuid();

        return parent::postJson($uri, $data, $headers, $options);
    }
}
