<?php

namespace Craft;

class CraftImportService extends BaseApplicationComponent
{
    public function __construct()
    {
		ini_set('max_execution_time', 9999);
    }

	public function loadEntries()
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
			    'title' => "Title ".$i,
				'slug' => "title-".$i
			));
			/*
			if ( craft()->entries->saveEntry($entry) )
			{
				$i++;
				continue;
			} else {
				$retVal = false;
				break;
			}*/
		}
		return $retVal;
	}

}
