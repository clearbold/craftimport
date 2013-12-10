<?php

namespace Craft;

class SeedTest_RecipesController extends BaseController
{
    
    public function actionPopulate()
    {
		if(craft()->seedTest->populateRecipes()){
			craft()->userSession->setNotice(Craft::t('Recipes Populated.'));
		}else{
			craft()->userSession->setError(Craft::t("Couldn't populate recipes."));
		}
		$this->redirect('seedtest');
    }

}
