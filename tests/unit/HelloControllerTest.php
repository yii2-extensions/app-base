<?php

declare(strict_types=1);

namespace app\tests\unit;

use app\tests\support\spies\HelloControllerSpy;
use Codeception\Test\Unit;
use yii\base\InvalidRouteException;
use yii\console\{Application, Exception, ExitCode};

/**
 * Unit tests for {@see \app\commands\HelloController} output behavior.
 *
 * Uses {@see HelloControllerSpy} to capture `stdout()` writes (Yii2's `Controller::stdout()` goes straight to the
 * `STDOUT` stream, bypassing PHPUnit / Codeception output buffering).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class HelloControllerTest extends Unit
{
    /**
     * @throws Exception if an unexpected error occurs during execution.
     * @throws InvalidRouteException if the action route is invalid.
     */
    public function testIndexActionOutputsCustomMessage(): void
    {
        $application = new Application(['id' => 'test', 'basePath' => dirname(__DIR__, 2)]);
        $controller = new HelloControllerSpy('hello', $application);

        $exitCode = $controller->runAction('index', ['custom message']);

        self::assertSame(
            "custom message\n",
            $controller->stdoutBuffer,
            'Output should match the custom message provided as an argument.',
        );
        self::assertSame(
            ExitCode::OK,
            $exitCode,
            'Exit code should be OK.',
        );
    }

    /**
     * @throws Exception if an unexpected error occurs during execution.
     * @throws InvalidRouteException if the action route is invalid.
     */
    public function testIndexActionOutputsDefaultMessage(): void
    {
        $application = new Application(['id' => 'test', 'basePath' => dirname(__DIR__, 2)]);
        $controller = new HelloControllerSpy('hello', $application);

        $exitCode = $controller->runAction('index');

        self::assertSame(
            "hello world\n",
            $controller->stdoutBuffer,
            'Output should match the default message.',
        );
        self::assertSame(
            ExitCode::OK,
            $exitCode,
            'Exit code should be OK.',
        );
    }
}
