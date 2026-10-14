<?php

declare(strict_types=1);

namespace DoubtQueue\Tests\Http;

use DoubtQueue\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /** A string that is not valid UTF-8, so json_encode cannot encode it. */
    private const INVALID_UTF8 = "\xB1\x31";

    private string $logFile = '';

    private string|false $previousLogFile = false;

    protected function setUp(): void
    {
        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'doubtqueue-log-');
        $this->previousLogFile = ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        if ($this->previousLogFile !== false) {
            ini_set('error_log', $this->previousLogFile);
        }

        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testSendWritesTheBodyAsJsonWithoutEscapingSlashes(): void
    {
        $response = new Response(200, ['status' => 'OPEN', 'path' => 'api/sessions']);

        $this->assertSame('{"status":"OPEN","path":"api/sessions"}', $this->capture($response));
    }

    public function testSendNeverWritesAnEmptyBodyWhenEncodingFails(): void
    {
        $response = new Response(200, ['student_name' => self::INVALID_UTF8]);

        $this->assertNotSame('', $this->capture($response));
    }

    public function testSendFallsBackToAnErrorBodyWhenEncodingFails(): void
    {
        $response = new Response(200, ['student_name' => self::INVALID_UTF8]);

        $body = json_decode($this->capture($response), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['status' => 500, 'message' => 'Something went wrong'], $body);
    }

    public function testSendLogsTheReasonWhenEncodingFails(): void
    {
        $this->capture(new Response(200, ['student_name' => self::INVALID_UTF8]));

        $this->assertStringContainsString(
            'JsonException',
            (string) file_get_contents($this->logFile)
        );
    }

    private function capture(Response $response): string
    {
        ob_start();

        try {
            $response->send();
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
