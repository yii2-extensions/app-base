<?php

declare(strict_types=1);

use app\models\User;
use app\tests\support\MailerBootstrap;
use yii\caching\FileCache;
use yii\rbac\PhpManager;
use yii\symfonymailer\{Mailer, Message};
use yii\web\JsonParser;

/** @phpstan-var array<string, mixed> $params */
$params = require __DIR__ . '/params.php';

return [
    'id' => 'app-test',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        MailerBootstrap::class,
    ],
    'viewPath' => '@app/resources/views',
    'components' => [
        'assetManager' => [
            'basePath' => __DIR__ . '/../public/assets',
        ],
        'authManager' => [
            'class' => PhpManager::class,
        ],
        'cache' => [
            'class' => FileCache::class,
        ],
        'db' => require __DIR__ . '/test_db.php',
        'mailer' => [
            'class' => Mailer::class,
            'messageClass' => Message::class,
            'useFileTransport' => true,
            'viewPath' => '@app/resources/mail',
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => JsonParser::class,
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'user' => [
            'identityClass' => User::class,
            'loginUrl' => ['user/login'],
        ],
    ],
    'controllerNamespace' => 'app\\controllers',
    'language' => 'en-US',
    'params' => [...$params, 'turnstile.secretKey' => ''],
];
