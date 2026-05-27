<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\site;

use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\site\HomeAction;
use Codeception\Test\Unit;

use function is_string;

/**
 * Unit tests for {@see HomeAction} home page rendering.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class HomeActionTest extends Unit
{
    public function testActionHome(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $action = new HomeAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Body must be non-empty.',
        );
        self::assertTrue(
            is_string($response),
            'Body must render as a string.',
        );
        self::assertStringContainsString(
            '<!DOCTYPE html>',
            $response,
            'Output must be wrapped in the main layout.',
        );
        self::assertStringContainsString(
            '<main>',
            $response,
            'Layout `<main>` region must wrap the view content.',
        );
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME']);

        parent::tearDown();
    }
}
