<?php

declare(strict_types=1);

use app\models\User;
use yii\caching\FileCache;
use yii\log\FileTarget;
use yii\mail\MailerInterface;
use yii\rbac\PhpManager;
use yii\symfonymailer\{Mailer, Message};
use yii\web\JsonParser;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'app',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'authManager' => [
            'class' => PhpManager::class,
        ],
        'cache' => [
            'class' => FileCache::class,
        ],
        'db' => $db,
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => FileTarget::class,
                    'levels' => [
                        'error',
                        'warning',
                    ],
                ],
            ],
            'traceLevel' => YII_DEBUG ? 3 : 0,
        ],
        'mailer' => MailerInterface::class,
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
            'parsers' => [
                'application/json' => JsonParser::class,
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'user' => [
            'enableAutoLogin' => true,
            'identityClass' => User::class,
            'loginUrl' => ['user/login'],
        ],
    ],
    'container' => [
        'singletons' => [
            MailerInterface::class => [
                'class' => Mailer::class,
                'messageClass' => Message::class,
                'useFileTransport' => true,
                'viewPath' => '@app/resources/mail',
            ],
        ],
    ],
    'controllerNamespace' => 'app\\controllers',
    'params' => $params,
    'viewPath' => '@app/resources/views',
];

return $config;
