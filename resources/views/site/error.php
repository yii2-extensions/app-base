<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\web\HttpException;

/**
 * @var string $message User-facing error message (already filtered by {@see \yii\web\ErrorAction}).
 * @var string $name Exception name derived by {@see \yii\web\ErrorAction}.
 * @var \Throwable $exception Captured exception (synthesized as `NotFoundHttpException` when none was attached).
 * @var \yii\web\View $this View component instance.
 */

$code = $exception instanceof HttpException ? $exception->statusCode : ($exception->getCode() ?: 500);

$this->title = "Error {$code}";
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::encode($message) ?></p>
