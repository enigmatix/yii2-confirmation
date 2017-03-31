<?php
/**
 * Created by PhpStorm.
 * User: joels
 * Date: 5/3/17
 * Time: 6:12 PM
 */

namespace enigmatix\confirmation;


use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\db\BaseActiveRecord;
use Yii;
use yii\helpers\ArrayHelper;



/**
 * Class ConfirmationBehavior
 *
 * @package enigmatix\confirmation
 */
class ConfirmationBehavior extends Behavior
{
    /**
     * @var bool whether to skip typecasting of `null` values.
     * If enabled attribute value which equals to `null` will not be type-casted (e.g. `null` remains `null`),
     * otherwise it will be converted according to the type configured at [[attributeTypes]].
     */
    public $skipOnNull = true;

    /**
     * @var array a list of the attributes to be protected, provided when constructing the behavior and attaching,
     *            or in the model's behaviors method.
     */
    public $protectedAttributes = [];

    /**
     * @var string If a release token has been supplied, provided the corresponding release object is valid, the change
     *             will be executed.
     */
    public $releaseToken;

    /**
     * @var array A list of roles that can bypass the protection and make the change without triggering a confirmation
     *            request.
     */
    public $allow = [];

    /**
     * @var string namespace of the object that stores and executes the ConfirmationRequest
     */
    public $confirmationRequestClass = 'enigmatix\\confirmation\\ConfirmationRequest';

    /**
     * @var string delivery method for the Confirmation Request.  Currently only email is supported.
     */
    public $secondFactor = 'email';


    /**
     * @var string the name of the variable to use to traverse to the user table from the secured model.
     */
    public $createdByAttribute = 'createdBy';

    /**
     * @var string
     */
    public $confirmationViewPath = '@vendor/enigmatix/yii2-confirmation/mail/_confirmationEmail';

    /**
     * @var string The name of the table attribute that tracks when a record is updated.  This value is ignored when
     *             determining of a record has changed values in it.
     */
    public $timestampAttribute = 'updated_at';
    /**
     * @inheritdoc
     */
    public function events()
    {

        return [BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave'];

    }

    /**
     * @param $event Event;
     */

    public function beforeSave($event) {
        $this->protectAttributes();
    }


    /**
     * Business logic around triggering the confirmation request.
     */
    protected function protectAttributes()
    {
        $user = Yii::$app->user;

        $changedValues = $this->getChangedValues();

        foreach ($changedValues as $attribute => $value) {

            if ($this->skipOnNull && $value === null || $attribute == $this->timestampAttribute) {
                            continue;
            }

            if (!$this->isAuthorised($user, $attribute, $value)) {
                $this->createConfirmationRequest();
                $this->resetAttribute($attribute);
            }
        }
    }

    /**
     * Checks whether a user is allowed to make the change without triggering a confirmation request.
     * @param \yii\web\User $user
     * @param string $attribute
     * @param string $value
     *
     * @return bool
     */
    protected function isAuthorised($user, $attribute, $value) {

        //Check for pre-defined administration roles
        if ($this->userIsAuthorised($user)) {
                    return true;
        }

        //Check for valid release token , eg that the token exists and is for the same record as this
        if ($this->releaseToken != null) {
            $confirmation = ConfirmationRequest::findOne(['release_token' => $this->releaseToken]);

            if ($confirmation == null) {
                            return false;
            }

            $model = $confirmation->constructObject();

            return $this->owner->getPrimaryKey(true) == $model->getPrimaryKey(true);

        }

        //Check to see if any protected attributes have been altered
        foreach ($this->protectedAttributes as $attribute) {
                    if ($this->hasChanged($attribute))
                return false;
        }

        return true;
    }

    /**
     * Iterates over roles to determine if the user is authorised to complete the action.
     * @param \yii\web\User $user
     *
     * @return bool
     */
    protected function userIsAuthorised($user) {
        foreach ($this->allow as $role) {
                    if ($user->can($role))
                return true;
        }

        return false;
    }

    /**
     * Business logic handling the creation of the Confirmation Request, and sending the second factor message.
     */
    protected function createConfirmationRequest() {

        $model         = $this->owner;
        $changedValues = $this->getChangedValues();

        /* @var ConfirmationRequest $request */
        
        $request = new $this->confirmationRequestClass([
            'model'  => $model->className(),
            'object' => serialize($model),
            'values' => serialize($changedValues),
        ]);

        $request->save();

        $this->sendSecondFactorMessage($request);
    }


    /**
     * Determines whether an attribute has been changed in the object.
     * @param string $attribute
     *
     * @return bool
     */
    protected function hasChanged($attribute) {
        return $this->owner->oldAttributes[$attribute] != $this->owner->{$attribute};
    }

    /**
     * Fetches all values which have changed, expect for the timestamp attribute.
     * @return array
     */
    public function getChangedValues() {
        $changedAttributes = [];

        foreach ($this->owner->attributes() as $attribute) {
                    if ($this->hasChanged($attribute))
                $changedAttributes[$attribute] = $this->owner->$attribute;
        }

        unset($changedAttributes[$this->timestampAttribute]);

        return $changedAttributes;
    }

    /**
     * Sets an attribute back to it's original value when it was fetched.
     * @param string $attribute
     */
    protected function resetAttribute($attribute) {
        $this->owner->$attribute = $this->owner->oldAttributes[$attribute];
    }

    /**
     * Adds a flash message to the interface stating the change has been held over pending confirmation.
     * @param ConfirmationRequest $model
     */
    public function createFeedbackMessage($model) {
        $this->displayMessage($model);
    }

    /**
     * Business logic around displaying an appropriate feedback message to the user regbarding the change.
     * @param ConfirmationRequest $model
     */
    protected function displayMessage($model) {
        Yii::$app->session->setFlash('warning', 'Your update is pending confirmation.  Please check your email for a confirmation link.');
    }

    /**
     * Business logic around transmitting the second factor message.
     * @param ConfirmationRequest $model
     */
    public function sendSecondFactorMessage($model) {
        switch ($this->secondFactor) {
            case 'email':
                Yii::$app->mailer
                    ->compose($this->confirmationViewPath, ['model' => $model])
                    ->setTo([$this->getEmail($model)])
                    ->send();
                $this->createFeedbackMessage($model);
                break;
            default:
                break;
        }
    }

    /**
     * Attempts to retrieve an address from several places within the request.  Firstly, attempts to find an email in the
     * changed values, and then looks within the current object for an email, and lastly attempts to traverse to the User
     * who created the object if an identifier has been recorded.
     *
     * @param ConfirmationRequest $model
     *
     * @return string
     * @throws InvalidCallException
     */
    protected function getEmail($model) {

        $values = unserialize($model->values);
        $email  = ArrayHelper::getValue($values, 'email');
        $object = $model->constructObject();

        if ($email == null) {
            $email = ArrayHelper::getValue($values, 'email_address');
        }

        if ($email == null) {
            $email = ArrayHelper::getValue($object, 'email');
        }

        if ($email == null) {
            $email = ArrayHelper::getValue($object, 'email_address');
        }

        if ($email == null) {
            $email = ArrayHelper::getValue($object, $this->createdByAttribute . '.email');
        }

        if ($email == null) {
            throw new InvalidCallException('Unable to locate email address via record, changed values, or user account');
        }

        return $email;
    }

}
