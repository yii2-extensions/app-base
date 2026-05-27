<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\user\GuestOnlyActionBehaviors;
use app\usecases\shared\view\ViewRendererInterface;
use Yii;
use yii\web\{Action, Response};

/**
 * Authenticates the user against {@see LoginForm}: on success redirects to the stored return URL; on validation failure
 * stores errors as a flash and re-renders the login view through the frontend overlay.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class LoginAction extends Action
{
    use GuestOnlyActionBehaviors;

    /**
     * Creates a new instance with the renderer supplied by the active frontend overlay.
     *
     * @param ViewRendererInterface $view Renderer used to produce the login view.
     */
    public function __construct(private readonly ViewRendererInterface $view)
    {
        parent::__construct();
    }

    /**
     * Handles the login request.
     *
     * @return Response|string Redirect to the stored return URL on success, a redirect back to the login route on
     * validation failure, or the rendered login view.
     */
    public function run(): Response|string
    {
        $model = new LoginForm();

        /** @var array<string, mixed> $post */
        $post = Yii::$app->request->post();

        if ($model->load($post) && $model->login()) {
            return Yii::$app->response->redirect(Yii::$app->user->getReturnUrl());
        }

        if (Yii::$app->request->isPost && $model->hasErrors()) {
            Yii::$app->session->setFlash('errors', $model->getErrors());

            return Yii::$app->response->redirect(['/user/login']);
        }

        return $this->view->render(
            'user/login',
            ['model' => $model],
        );
    }
}
