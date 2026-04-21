<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var int $status HTTP status code of the error.
 * @var string $message Human-readable error message.
 * @var \yii\web\View $this View component instance.
 */
$this->title = "Error {$status}";
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::encode($message) ?></p>
