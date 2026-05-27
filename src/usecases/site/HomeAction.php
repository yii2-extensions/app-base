<?php

declare(strict_types=1);

namespace app\usecases\site;

use app\usecases\shared\view\ViewRendererInterface;
use yii\web\{Action, Response};

/**
 * Renders the application home page through the frontend overlay's {@see ViewRendererInterface}.
 *
 * Route: `site/home` (also reached through the application `defaultRoute`).
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class HomeAction extends Action
{
    /**
     * Creates a new instance with the renderer supplied by the active frontend overlay.
     *
     * @param ViewRendererInterface $view Renderer used to produce the home page.
     */
    public function __construct(private readonly ViewRendererInterface $view)
    {
        parent::__construct();
    }

    /**
     * Renders the home page.
     *
     * @return Response|string Rendered home view, or an `HTTP` response when the overlay sends headers directly.
     */
    public function run(): Response|string
    {
        return $this->view->render(
            'site/home',
        );
    }
}
