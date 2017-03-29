<?php
return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/main.php'),
    require(__DIR__ . '/main-local.php'),
    require(__DIR__ . '/test.php'),
    [
        'components' => [
            'db' => [
                'class'     => 'yii\\db\\Connection',
                'dsn'       => 'mysql:host=localhost;dbname=confirmationtest',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8',
            ],
            'user' => [
                'identityClass' => 'enigmatix\confirmation\tests\models\User',
            ],
            'mailer' => [
                'class' => 'yii\swiftmailer\Mailer',
            ],

        ],
    ]
);
