<?php

namespace Craft;

class SeedTestService extends BaseApplicationComponent
{
    public function __construct()
    {
		ini_set('max_execution_time', 9999);
    }

    public function populateUsers()
    {
		/* This takes a good... 25mins. Only do on local. Otherwise we'll create a batch */
		$total = (int) craft()->db->createCommand("SELECT count(1) FROM craft_users")->queryScalar();
		$i = ($total+1);
		$retVal = true;
		while($i <= 300000)
		{
			$user = new UserModel();
			$user->setAttributes(array(
				"username" => "seedtest_".$i,
				"email" => "seedtest_".$i."@cos.dev",
				"status" => "active"
			));
			if ( craft()->users->saveUser($user) )
			{
				$i++;
				continue;
			} else {
				$retVal = false;
				break;
			}
		}
		return $retVal;
    }

    public function populateRecipes()
    {
		$total = (int) craft()->db->createCommand("SELECT count(1) FROM craft_entries WHERE sectionId = 8")->queryScalar();
		$i = ($total+1);
		$retVal = true;
		while($i <= 600)
		{
			$entry = new EntryModel();
			$entry->sectionId  = 8;
			$entry->typeId     = 10;
			$entry->authorId   = 1;
			$entry->enabled    = true;
			$entry->getContent()->setAttributes(array(
			    'title' => "Recipe Seed ".$i,
				'slug' => "recipes-seed-".$i
			));
			if ( craft()->entries->saveEntry($entry) )
			{
				$i++;
				continue;
			} else {
				$retVal = false;
				break;
			}
		}
		return $retVal;
    }

	public function populateProductGroups()
	{
		$total = (int) craft()->db->createCommand("SELECT count(1) FROM craft_entries WHERE sectionId = 7 AND typeId = 9")->queryScalar();
		$i = ($total+1);
		$retVal = true;
		while($i <= 30)
		{
			$entry = new EntryModel();
			$entry->sectionId  = 7;
			$entry->typeId     = 9;
			$entry->authorId   = 1;
			$entry->enabled    = true;
			$entry->getContent()->setAttributes(array(
			    'title' => "Product Group ".$i,
				'slug' => "product-seed-".$i
			));
			if ( craft()->entries->saveEntry($entry) )
			{
				$i++;
				continue;
			} else {
				$retVal = false;
				break;
			}
		}
		return $retVal;
	}

}
