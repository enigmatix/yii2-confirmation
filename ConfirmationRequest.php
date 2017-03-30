<?php

namespace enigmatix\confirmation;

use yii\base\ErrorException;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use Yii;
use yii\helpers\Url;


/**
 * This is the model class for table "{{%confirmation_request}}".
 *
 * @property integer $id
 * @property string $model
 * @property integer $object_id
 * @property string $object
 * @property string $release_token
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $values
 * @property integer $created_by
 * @property integer $updated_by
 *
 * @property User $createdBy
 * @property User $updatedBy
 */
class ConfirmationRequest extends \yii\db\ActiveRecord
{

    protected $delivery = 'display';

    protected $secondFactor = 'email';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%confirmation_request}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['object_id'], 'integer'],
            [['object', 'values'], 'string'],
            [['model', 'release_token'], 'string', 'max' => 255],
            [['release_token'], 'default', 'value' => function($model, $attribute) { return $this->generateReleaseToken(); }],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => $this->getUserClassName(), 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => $this->getUserClassName(), 'targetAttribute' => ['updated_by' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'model' => Yii::t('app', 'Model'),
            'object_id' => Yii::t('app', 'Object'),
            'object' => Yii::t('app', 'Object'),
            'release_token' => Yii::t('app', 'Release Token'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'values' => Yii::t('app', 'Values'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }


    /**
     * @inheritdoc
     */
    public function relations()
    {
        return [
            'CreatedBy' => 'one',
            'UpdatedBy' => 'one',
        ];
    }

    public function behaviors() {

        return ArrayHelper::merge(parent::behaviors(),
            [
                TimestampBehavior::className(),
                BlameableBehavior::className(),
            ]);
    }

    public function getViewLink() {
        return Url::to(['@web/confirmation-requests', 'release_token' => $this->release_token], true);
    }

    /**
     * Generates new password reset token
     */
    public function generateReleaseToken()
    {
        return Yii::$app->security->generateRandomString() . '_' . time();
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne($this->getUserClassName(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne($this->getUserClassName(), ['id' => 'updated_by']);
    }

    /**
     * @return string ActiveRecord user class, as per application implementation
     */
    protected function getUserClassName() {
        return Yii::$app->user->identityClass;
    }

    public function release() {

        $model          = $this->constructObject();
        $changedValues  = $model->getChangedValues();
        $current        = clone $model;
        $current->refresh();

        foreach ($changedValues as $field => $value) {
            $oldValue = $model->oldAttributes[$field];
            if ($current->$field !== $oldValue && $current->$field !== $value) {
                throw new ErrorException(
                    sprintf('Unable to release change, protected field %s has been updated since this request.'
                    . ' Expected to find %s or %s, found %s', $field, $value, $oldValue, $current->$field));
            }

        }

        $model->releaseToken = $this->release_token;
        return $model->save();

    }


    /**
     * @return \enigmatix\core\Model
     */
    public function constructObject() {
        return unserialize($this->object);
    }


}
