<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\user\IndexAction;
use Codeception\Test\Unit;
use Yii;

/**
 * Unit tests for {@see IndexAction} user listing through `UserSearch`.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class IndexActionTest extends Unit
{
    /**
     * @return array{user: array{class: string, dataFile: string}}
     */
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                // @phpstan-ignore binaryOp.invalid
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function testActionIndexReturnsResponse(): void
    {
        $_SERVER['REQUEST_URI'] = '/user';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Yii::$app->requestedRoute = 'user/index';

        $action = new IndexAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Body must be non-empty.',
        );
    }

    protected function tearDown(): void
    {
        Yii::$app->requestedRoute = '';

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
