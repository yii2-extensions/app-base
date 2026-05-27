<?php

declare(strict_types=1);

namespace app\usecases\shared\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

/**
 * Validates the CSRF token before a standalone action runs.
 *
 * Standalone actions have no hosting {@see \yii\web\Controller}, so the CSRF check that normally runs in
 * {@see \yii\web\Controller::beforeAction()} never fires. Attach this filter through an action
 * {@see \yii\base\Action::behaviors()} to restore that protection on state-changing routes; actions that must accept
 * cross-origin requests (for example, a stateless API endpoint) simply omit it.
 *
 * Enforcement applies only to unsafe HTTP methods and only when {@see \yii\web\Request::$enableCsrfValidation} is on;
 * {@see \yii\web\Request::validateCsrfToken()} returns `true` for GET, HEAD, and OPTIONS.
 *
 * Usage example:
 * ```php
 * public function behaviors(): array
 * {
 *     return [
 *         'csrf' => \app\usecases\shared\filters\CsrfValidationFilter::class,
 *     ];
 * }
 * ```
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class CsrfValidationFilter extends ActionFilter
{
    /**
     * Rejects the request when the CSRF token is missing or invalid on an unsafe `HTTP` method.
     *
     * @param mixed $action Action about to run.
     *
     * @throws BadRequestHttpException if the CSRF token cannot be verified.
     *
     * @return bool `true` when the token is valid or validation does not apply.
     */
    public function beforeAction($action): bool
    {
        if (Yii::$app->getRequest()->validateCsrfToken() === false) {
            throw new BadRequestHttpException(
                Yii::t('yii', 'Unable to verify your data submission.'),
            );
        }

        return true;
    }
}
