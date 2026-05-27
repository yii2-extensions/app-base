<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\site;

use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\site\AboutAction;
use Codeception\Test\Unit;

/**
 * Unit tests for {@see AboutAction} static page rendering.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class AboutActionTest extends Unit
{
    public function testActionAbout(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/about';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $action = new AboutAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Body must be non-empty.',
        );
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
