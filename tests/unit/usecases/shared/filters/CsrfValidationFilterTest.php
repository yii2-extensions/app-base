<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\shared\filters;

use Codeception\Test\Unit;
use Yii;
use yii\web\BadRequestHttpException;

/**
 * Unit tests for {@see \app\usecases\shared\filters\CsrfValidationFilter} applied to standalone actions.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class CsrfValidationFilterTest extends Unit
{
    public function testAllowsSafeMethodWithoutCsrfToken(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Yii::$app->request->enableCsrfValidation = true;

        $response = Yii::$app->runAction('site/contact');

        self::assertNotEmpty(
            $response,
            'Safe method must bypass CSRF validation.',
        );
    }

    public function testThrowBadRequestHttpExceptionWhenCsrfTokenIsMissingOnPost(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->enableCsrfValidation = true;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Unable to verify your data submission.',
        );

        Yii::$app->runAction('site/contact');
    }

    protected function tearDown(): void
    {
        Yii::$app->request->enableCsrfValidation = false;

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
