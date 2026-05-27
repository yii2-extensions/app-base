<?php

declare(strict_types=1);

use yii\mail\MessageInterface;
use yii\web\View;

/**
 * @var string $content Main view render result.
 * @var MessageInterface $message Message being composed.
 * @var View $this View component instance.
 */
?>
<?php $this->beginPage() ?>
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
<?php $this->endPage();
