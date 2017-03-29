<?php
return
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

        ]
];
