<?php

namespace Craft;

class CraftImport_EntriesController extends BaseController
{

    public function actionLoad()
    {
        if(craft()->craftImport->loadEntries()){
            craft()->userSession->setNotice(Craft::t('Entries loaded.'));
        }else{
            craft()->userSession->setError(Craft::t("Couldn't populate entries."));
        }
        $this->redirect('craftimport');
    }

}
