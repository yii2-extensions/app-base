<?php

declare(strict_types=1);

namespace app\usecases\shared\user;

use Yii;
use yii\filters\AccessControl;

/**
 * Provides the shared {@see AccessControl} rule for standalone user slices reserved to anonymous visitors (login,
 * signup, password reset request, verification email resend).
 *
 * Authenticated users hitting the slice are redirected to the home page via `denyCallback`; anonymous users pass
 * through.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
trait GuestOnlyActionBehaviors
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => false,
                        'roles' => ['@'],
                        'denyCallback' => static function (): void {
                            Yii::$app->response->redirect(Yii::$app->homeUrl);
                        },
                    ],
                    ['allow' => true, 'roles' => ['?']],
                ],
            ],
        ];
    }
}
