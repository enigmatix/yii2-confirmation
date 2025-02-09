<?php

/* @var $model /yii/base/Model */
use yii\helpers\Html;

$link = $model->getViewLink();
?>

<p>Your details have been changed in <?=Yii::$app->name ?>, specifically:</p>

  <?=  Html::ul(unserialize($model->values), ['item' => function($item, $index) {return Html::tag('li', $index . ' : ' . $item); }]) ?>

<p>If these details are correct, please follow this link to 
    finalise your changes: <?= Html::a('Finalise link', $model->getViewLink()) ?></p>

<p>
    If you did not make these changes, we recommend you log in and change your password immediately.
</p>