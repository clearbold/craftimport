<?php

namespace Craft;

class SeedTest_ProductsController extends BaseController
{
    
    public function actionPopulate()
    {
		if(craft()->seedTest->populateProducts()){
			craft()->userSession->setNotice(Craft::t('Products Populated.'));
		}else{
			craft()->userSession->setError(Craft::t("Couldn't populate products."));
		}
		$this->redirect('seedtest');
    }

}
