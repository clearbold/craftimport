<?php

namespace Craft;

class CraftImport_EntriesController extends BaseController
{

    public function actionLoad()
    {
		if(craft()->craftImport->loadEntries()){
			craft()->craftImport->setNotice(Craft::t('Entries loaded.'));
		}else{
			craft()->craftImport->setError(Craft::t("Couldn't populate entries."));
		}
		$this->redirect('import');
    }

}
