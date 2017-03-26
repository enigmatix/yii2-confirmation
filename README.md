Change Control Behavior
=======================
This behavior protects variable(s) in a model from being changed by sending a confirmation request
via email.  This is ideally used for secure information, such as email addresses connected to user accounts, where you
want to ensure the user has access to the new email address before commiting the change.

Once a user attempts to change an email, the request and the object are stored, and the release token is sent either to
the new email address, or if another attribute is changed, to the current email address of the user.

The functionality traverses the 'createdBy' link to the user's table.  If no email is found in the model, and no email
can be retrieved from the createdBy link, an exception will be thrown.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist enigmatix/yii2-confirmation "*"
```

or add

```
"enigmatix/yii2-confirmation": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed:

1. Run the migration
```
./yii migrate --migrationPath=@vendor/enigmatix/yii2-confirmation/migration

```

2. Add the behavior to the appropriate model.

```

Class User extends ActiveRecord {

...
    public function behaviors()
    {
        return [
        ...
            [
                'class'                 => ConfirmationBehavior::className(),
                'protectedAttributes'   => ['email'], //your attribute name here
              //'allow'                 => ['roleA', ['roleB']

            ],
        ];
    }
}
```

The 'allow' node is optional, and only required if you want certain roles to be
excluded from generating a confirmation.  Often useful for admin or other privileged users.

3. Add the controller to your frontend or app config/main.php

```
return [
...
    'controllerMap' => [
        'confirmation-requests' => 'enigmatix\confirmation\ConfirmationRequestsController'
    ],
];
```