<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\usecases\user\LogoutAction;
use Codeception\Test\Unit;

/**
 * Unit tests for {@see LogoutAction} session termination.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class LogoutActionTest extends Unit
{
    public function testActionLogout(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/logout';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $action = new LogoutAction();

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Body must be a redirect response.',
        );
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
