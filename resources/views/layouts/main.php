<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var string $content Inner view content to render inside the layout.
 * @var \yii\web\View $this View component instance.
 */
$session = Yii::$app->session;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title ?? Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<nav>
    <a href="<?= Yii::$app->urlManager->createUrl(['site/index']) ?>"><?= Html::encode(Yii::$app->name) ?></a>
    <a href="<?= Yii::$app->urlManager->createUrl(['site/about']) ?>">About</a>
    <a href="<?= Yii::$app->urlManager->createUrl(['site/contact']) ?>">Contact</a>
    <?php if (Yii::$app->user->isGuest) { ?>
        <a href="<?= Yii::$app->urlManager->createUrl(['user/signup']) ?>">Sign up</a>
        <a href="<?= Yii::$app->urlManager->createUrl(['user/login']) ?>">Login</a>
    <?php } else { ?>
        <?php if (Yii::$app->user->can('viewUsers')) { ?>
            <a href="<?= Yii::$app->urlManager->createUrl(['user/index']) ?>">Users</a>
        <?php } ?>
        <?= Html::beginForm(['user/logout'], 'post') ?>
        <?= Html::submitButton('Logout') ?>
        <?= Html::endForm() ?>
    <?php } ?>
</nav>
<?php foreach (['success', 'error'] as $level) { ?>
    <?php if ($session->hasFlash($level)) { ?>
        <div role="alert" class="flash flash-<?= $level ?>">
            <?= Html::encode((string) $session->getFlash($level)) ?>
        </div>
    <?php } ?>
<?php } ?>
<?php
$errorBag = $session->getFlash('errors');
if (is_array($errorBag) && $errorBag !== []) { ?>
    <ul role="alert" class="flash flash-errors">
        <?php foreach ($errorBag as $attribute => $messages) { ?>
            <?php foreach ((array) $messages as $message) { ?>
                <li><?= Html::encode((string) $message) ?></li>
            <?php } ?>
        <?php } ?>
    </ul>
<?php } ?>
<main>
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
