<?php

namespace enigmatix\confirmation\tests\functional;

use enigmatix\confirmation\ConfirmationRequest;
use Yii;
use frontend\models\PasswordResetRequestForm;
use common\fixtures\User as UserFixture;
use common\models\User;
use yii\base\ErrorException;

class confirmationTest extends \Codeception\Test\Unit
{
    /**
     * @var \frontend\tests\UnitTester
     */
    protected $tester;

    protected $errorCaught;

    public function fixtures(){
        return [
            'users' => UserFixture::className(),
        ];
    }

    public function _before()
    {
        $this->tester->haveFixtures([
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
        ]);
    }

    public function testTryChange()
    {

        $attribute          = 'email';
        $userFixture        = $this->tester->grabFixture('user', 0);
        $firstValue         = 'test@place.com';
        $secondValue        = 'other@email.com';

        $user               = User::findOne($userFixture['id']);
        $user->$attribute   = $firstValue;
        $user->save();
        $request            = ConfirmationRequest::find()->one();

        $user->refresh();

        expect_not($firstValue == $user->$attribute);
        $this->tester->seeEmailIsSent();

        $email = $this->tester->grabLastSentEmail();

        expect('Confirmation email sent to intended recipient', $email->getTo())->equals([$firstValue => null]);
        $user->$attribute   = $secondValue;
        $user->save();

        $secondRequest      = ConfirmationRequest::find()->where(['!=', 'id', $request->id])->one();
        $user->refresh();

        expect($attribute . ' has not changed', $user->$attribute)->notEquals($secondValue);
        $this->tester->seeEmailIsSent();

        $email = $this->tester->grabLastSentEmail();

        expect('Confirmation email sent to intended recipient', $email->getTo())->equals([$secondValue => null]);

        $secondRequest->release();
        $user->refresh();

        expect($attribute . ' has now changed', $user->$attribute)->equals($secondValue);
        $errorCaught = false;

        try{
            $request->release();
        } catch (ErrorException $e){
            $errorCaught = true;
        }

        expect("Expired request cannot be released", $errorCaught)->true();

        $user->refresh();
        expect($attribute . ' has changed', $user->$attribute)->notEquals($firstValue);
    }

}
