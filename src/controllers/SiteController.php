<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\Base\AbstractSiteController;
use app\models\ContactForm;

/**
 * Default PHP-view implementation of {@see AbstractSiteController}.
 *
 * Frontend overlays may replace this file via the `yii2-extensions/scaffold` plugin to render with a different
 * presentation strategy (Inertia, JSON, etc.).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class SiteController extends AbstractSiteController
{
    protected function renderAbout(): string
    {
        return $this->render('about');
    }

    protected function renderContact(ContactForm $model): string
    {
        return $this->render('contact', ['model' => $model]);
    }

    protected function renderError(int $status, string $message): string
    {
        return $this->render('error', ['status' => $status, 'message' => $message]);
    }

    protected function renderIndex(): string
    {
        return $this->render('index');
    }
}
