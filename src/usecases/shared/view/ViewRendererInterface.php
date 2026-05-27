<?php

declare(strict_types=1);

namespace app\usecases\shared\view;

use yii\web\Response;

/**
 * Renders a named view into a string body or an HTTP response.
 *
 * Decouples per-feature standalone actions in `app\usecases` from the presentation strategy of the consuming frontend
 * overlay (PHP views for jQuery; Inertia for Vue/React). Each overlay binds its own implementation in the DI container
 * under `app\usecases\shared\view\ViewRendererInterface`, so actions remain agnostic to how the view is produced.
 *
 * View names are route-qualified (`site/about`, `user/login`) so overlays can map them deterministically without
 * inspecting the calling action.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
interface ViewRendererInterface
{
    /**
     * Renders the named view with the supplied parameters.
     *
     * @param string $view Route-qualified view name (for example, `site/about`, `user/login`).
     * @param array<string, mixed> $params Data to expose to the view layer.
     *
     * @return Response|string Rendered body, or an `HTTP` response when the overlay needs to send headers directly.
     */
    public function render(string $view, array $params = []): Response|string;
}
