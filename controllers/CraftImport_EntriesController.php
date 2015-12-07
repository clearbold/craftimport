<?php

namespace Craft;

class CraftImport_EntriesController extends BaseController
{

    public function actionLoad()
    {
        $retVal = craft()->craftImport->loadEntries();
        if($retVal > 0){
            craft()->userSession->setNotice(Craft::t($retVal . ' entries loaded.'));
        }else{
            craft()->userSession->setError(Craft::t("Couldn't populate entries."));
        }
        $this->redirect('craftimport');
    }

}
