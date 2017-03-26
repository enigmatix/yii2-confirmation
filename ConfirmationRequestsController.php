<?php

namespace enigmatix\confirmation;

use yii\web\Controller;
use Yii;
use enigmatix\confirmation\ConfirmationRequest;
use yii\base\ErrorException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ConfirmationRequestsController implements the CRUD actions for ConfirmationRequest model.
 */
class ConfirmationRequestsController extends Controller
{

    var $defaultAction = 'release';
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }


    /**
     * Releases a single change as stored in the  ConfirmationRequest model.
     * @param integer $id
     * @return mixed
     */
    public function actionRelease($release_token)
    {

        try{
            $model =  $this->findModel($release_token);
        } catch (NotFoundHttpException $e){
            Yii::$app->session->setFlash('danger' , "We were not able to find your change.  Perhaps it has already been processed?");
            return $this->goHome();
        }

        try{
            $model->release();
        } catch (ErrorException $e){
            return $this->redirect(['confirmation-requests/expired']);
        }

        $viewLink = $model->constructObject()->getViewLink();
        $model->delete();

        if(!Yii::$app->user->isGuest){
            return $this->redirect($viewLink);
        }

        return $this->renderOutput('@vendor/enigmatix/yii2-confirmation/views/confirm', []);
    }

    public function actionExpired(){
        return $this->renderOutput('@vendor/enigmatix/yii2-confirmation/views/expired', []);

    }

    /**
     * Finds the ConfirmationRequest model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ConfirmationRequest the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($release_token)
    {
        if (($model = ConfirmationRequest::findOne(['release_token' => $release_token])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
