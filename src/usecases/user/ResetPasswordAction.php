<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\view\ViewRendererInterface;
use Throwable;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\{Action, BadRequestHttpException, Response};

/**
 * Validates a single-use password reset token and persists the new password through {@see ResetPasswordForm}.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ResetPasswordAction extends Action
{
    /**
     * Creates a new instance with the renderer supplied by the active frontend overlay.
     *
     * @param ViewRendererInterface $view Renderer used to produce the reset-password view.
     */
    public function __construct(private readonly ViewRendererInterface $view)
    {
        parent::__construct();
    }

    /**
     * Handles the reset-password request for the supplied token.
     *
     * @param string $token Single-use password reset token from the route.
     *
     * @throws BadRequestHttpException if the token is invalid.
     *
     * @return Response|string Redirect to the home page on success, a redirect back to the form on failure, or the
     * rendered reset-password view.
     */
    public function run(string $token): Response|string
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        /** @var array<string, mixed> $post */
        $post = Yii::$app->request->post();

        if ($model->load($post)) {
            try {
                $saved = $model->validate() && $model->resetPassword();
            } catch (Throwable $e) {
                Yii::error(
                    $e->getMessage(),
                    __METHOD__,
                );

                $saved = false;
            }

            if ($saved) {
                Yii::$app->session->setFlash('success', 'New password saved.');

                return Yii::$app->response->redirect(Yii::$app->homeUrl);
            }

            if ($model->hasErrors()) {
                Yii::$app->session->setFlash('errors', $model->getErrors());
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to save your new password at this time.',
                );
            }

            return Yii::$app->response->redirect(['/user/reset-password', 'token' => $token]);
        }

        return $this->view->render(
            'user/reset-password',
            [
                'model' => $model,
                'token' => $token,
            ],
        );
    }
}
