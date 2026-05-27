<?php

declare(strict_types=1);

use app\usecases\shared\user\User;
use app\usecases\shared\view\{PhpViewRenderer, ViewRendererInterface};
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
            'loginUrl' => ['/user/login'],
        ],
    ],
    'container' => [
        'definitions' => [
            ViewRendererInterface::class => PhpViewRenderer::class,
        ],
        'singletons' => [
            MailerInterface::class => [
                'class' => Mailer::class,
                'messageClass' => Message::class,
                'useFileTransport' => true,
                'viewPath' => '@app/resources/mail',
            ],
        ],
    ],
    'actionNamespace' => 'app\\usecases',
    'defaultRoute' => 'site/home',
    'params' => $params,
    'viewPath' => '@app/resources/views',
];

if (YII_ENV === 'dev') {
    $config['bootstrap'][] = 'debug';
    $config['modules'] = [
        'debug' => [
            'class' => \yii\debug\Module::class,
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ],
    ];
}

return $config;
