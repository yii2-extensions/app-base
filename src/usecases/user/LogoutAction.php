<?php

declare(strict_types=1);

namespace app\usecases\user;

use Yii;
use yii\filters\{AccessControl, VerbFilter};
use yii\web\{Action, Response};

/**
 * Terminates the authenticated session and redirects to the home page.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class LogoutAction extends Action
{
    /**
     * Restricts the action to authenticated users (`@`) and to POST requests.
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['*' => ['POST']],
            ],
        ];
    }

    /**
     * Logs the current user out and redirects to the home page.
     *
     * @return Response Redirect to the application home URL.
     */
    public function run(): Response
    {
        Yii::$app->user->logout();

        return Yii::$app->response->redirect(Yii::$app->homeUrl);
    }
}
