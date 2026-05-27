<?php

declare(strict_types=1);

namespace app\usecases\shared\view;

use Yii;

/**
 * Default {@see ViewRendererInterface} implementation that resolves PHP view templates under `@app/resources/views`.
 *
 * Used by `app-base` out of the box and by the `frontend-jquery` overlay. Frontend overlays based on Inertia bind a
 * different implementation in the DI container.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class PhpViewRenderer implements ViewRendererInterface
{
    public function render(string $view, array $params = []): string
    {
        return Yii::$app->view->renderFile(
            Yii::getAlias("@app/resources/views/{$view}.php"),
            $params,
        );
    }
}
