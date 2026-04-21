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
    <?= Html::label('Name', 'contactform-name') ?>
    <?= Html::activeTextInput($model, 'name') ?>
</p>
<p>
    <?= Html::label('Email', 'contactform-email') ?>
    <?= Html::activeInput('email', $model, 'email') ?>
</p>
<p>
    <?= Html::label('Phone', 'contactform-phone') ?>
    <?= Html::activeTextInput($model, 'phone', ['placeholder' => '(999) 999-9999']) ?>
</p>
<p>
    <?= Html::label('Subject', 'contactform-subject') ?>
    <?= Html::activeTextInput($model, 'subject') ?>
</p>
<p>
    <?= Html::label('Body', 'contactform-body') ?>
    <?= Html::activeTextarea($model, 'body', ['rows' => 6]) ?>
</p>
<p>
    <?= Html::activeHiddenInput($model, 'turnstileToken') ?>
</p>
<p>
    <?= Html::submitButton('Send') ?>
</p>
<?= Html::endForm() ?>
