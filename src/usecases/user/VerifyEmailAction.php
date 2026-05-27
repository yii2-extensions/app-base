<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\user\VerifyEmailForm;
use app\usecases\shared\view\ViewRendererInterface;
use yii\base\InvalidArgumentException;
use yii\filters\VerbFilter;
use yii\web\{Action, BadRequestHttpException, Response};

/**
 * Renders the email verification interstitial form for the supplied token.
 *
 * Performs only token validation; the actual verification is committed by the `user/confirm-email` slice on POST, so
 * that single-use tokens are not silently consumed by email link scanners (Outlook SafeLinks, antivirus, browser
 * prefetch) before the recipient clicks.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class VerifyEmailAction extends Action
{
    /**
     * Creates a new instance with the renderer supplied by the active frontend overlay.
     *
     * @param ViewRendererInterface $view Renderer used to produce the verify-email view.
     */
    public function __construct(private readonly ViewRendererInterface $view)
    {
        parent::__construct();
    }

    /**
     * Restricts the action to GET requests.
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['*' => ['GET']],
            ],
        ];
    }

    /**
     * Validates the token and renders the verification interstitial.
     *
     * @param string $token Single-use verification token from the route.
     *
     * @throws BadRequestHttpException if the token is invalid.
     *
     * @return Response|string Rendered interstitial view, or an `HTTP` response when the overlay sends headers
     * directly.
     */
    public function run(string $token): Response|string
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->view->render(
            'user/verify-email',
            [
                'model' => $model,
                'token' => $token,
            ],
        );
    }
}
