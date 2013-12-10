<?php

namespace Craft;

class SeedTest_UsersController extends BaseController
{
    
    public function actionPopulate()
    {
		if(craft()->seedTest->populateUsers()){
			craft()->userSession->setNotice(Craft::t('Users Populated.'));
		}else{
			craft()->userSession->setError(Craft::t("Couldn't populate users."));
		}
		$this->redirect('seedtest');
    }

}
