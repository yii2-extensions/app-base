<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var \app\models\ContactForm $model Contact form model.
 * @var \yii\web\View $this View component instance.
 */
$this->title = 'Contact';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm('', 'post') ?>
<p>
    <?= Html::activeLabel($model, 'name', ['label' => 'Name']) ?>
    <?= Html::activeTextInput($model, 'name') ?>
</p>
<p>
    <?= Html::activeLabel($model, 'email', ['label' => 'Email']) ?>
    <?= Html::activeInput('email', $model, 'email') ?>
</p>
<p>
    <?= Html::activeLabel($model, 'phone', ['label' => 'Phone']) ?>
    <?= Html::activeTextInput($model, 'phone', ['placeholder' => '(999) 999-9999']) ?>
</p>
<p>
    <?= Html::activeLabel($model, 'subject', ['label' => 'Subject']) ?>
    <?= Html::activeTextInput($model, 'subject') ?>
</p>
<p>
    <?= Html::activeLabel($model, 'body', ['label' => 'Body']) ?>
    <?= Html::activeTextarea($model, 'body', ['rows' => 6]) ?>
</p>
<p>
    <?= Html::activeHiddenInput($model, 'turnstileToken') ?>
</p>
<p>
    <?= Html::submitButton('Send') ?>
</p>
<?= Html::endForm() ?>
