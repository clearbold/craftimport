<?php

namespace Craft;

class CraftImport_TestMatrixController extends BaseController
{

    public function actionTest()
    {
        $retVal = craft()->craftImport->testMatrix();
        if($retVal > 0){
            craft()->userSession->setNotice(Craft::t($retVal . ' entries loaded.'));
        }else{
            craft()->userSession->setError(Craft::t("Tested Matrix."));
        }
        $this->redirect('craftimport');
    }

}
