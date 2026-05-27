<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\user\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\VerbFilter;
use yii\web\{Action, BadRequestHttpException, Response};

/**
 * Commits the email verification submitted from the interstitial form.
 *
 * Performing the actual verification only on `POST` prevents email link scanners (Outlook SafeLinks, antivirus, browser
 * prefetch) from silently consuming the single-use token before the recipient clicks.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ConfirmEmailAction extends Action
{
    /**
     * Restricts the action to POST requests.
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['*' => ['POST']],
            ],
        ];
    }

    /**
     * Commits the email verification for the supplied token.
     *
     * @param string $token Single-use verification token from the route.
     *
     * @throws BadRequestHttpException if the token is invalid.
     *
     * @return Response Redirect to the home page, with a success or error flash depending on the outcome.
     */
    public function run(string $token): Response
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->verifyEmail() !== null) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');

            return Yii::$app->response->redirect(Yii::$app->homeUrl);
        }

        Yii::$app->session->setFlash(
            'error',
            'Sorry, we are unable to verify your account with provided token.',
        );

        return Yii::$app->response->redirect(Yii::$app->homeUrl);
    }
}
