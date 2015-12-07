<?php

namespace Craft;

class CraftImportService extends BaseApplicationComponent
{
    public function __construct()
    {

    }

    public function testMatrix()
    {
        $retVal = 0;

        // This should not be run on re-import!

        // Need to re-run reference materials loop and capture legacy ID, because that's what we're matching
        // the XML on

        // Subjects & How-To loops need entry ID, Field ID, Type ID
        // foreach block for each XML node with related entries

        // Treatise entry to test, ID: 2344 / Legacy ID: 398
        $entry = craft()->elements->getCriteria(ElementType::Entry);
        $entry->section = 'investmentAdvisersSubjects';
        $entry->limit = 1;
        $entry->id = 2344;

        $existingEntry = $entry->first();

        echo $existingEntry->slug . '<br />';

        $block = new MatrixBlockModel();
        $block->fieldId = 48;
        $block->ownerId = 2344;
        $block->typeId = 1;
        $block->getContent()->setAttributes(array(
            'entry' => [975],
            'annotation' => '<p>test</p>',
        ));
        craft()->matrix->saveBlock($block);

        // Test creating a Red Zone block

        // 1st attempt to fetch the block
        // http://craftcms.stackexchange.com/a/10043/187
        /*
        // THIS DOESN'T WORK
        // $block = new MatrixBlockModel($post['kidId']);

        // THIS WORKS
        $block = craft()->matrix->getBlockById($post['kidId']);

        $block->fieldId    = $kidsMatrix->id; // Matrix field's ID, 'Kids'
        $block->ownerId    = $currentUser->id; // ID of entry the block should be added to
        $block->typeId     = $kidBlock->id; // ID of block type, 'kid'
        $block->getContent()->setAttributes(array(
            'wooly' => $post['myWooly'],
        ));

        $response = craft()->matrix->saveBlock($block);
        */

        // http://craftcms.stackexchange.com/a/1686/187
        /*
        $entry = craft()->entries->getEntryById(123);
        $matrixBlocks = $entry->matrixFieldHandle;
        foreach ($matrixBlocks as $block){
            echo $block->id;
            echo $block->fieldHandle;
        }
        */

        // If no block, create a new block

        // Set the fields on the block

        // Save the block

        exit;

        return $retVal;
    }

    public function loadEntries()
    {
        $totalPosts = 0;
        $retVal = $totalPosts;

        /* REFERENCE MATERIALS */
        // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
        $categoriesXml = simplexml_load_file('http://40act.ee/export/categories');
        //echo 'XML loaded<br />';
        $tagSetId = 1;
        $sectionId = 0; // Visit settings for your Section and check the URL
        $typeId = 0; // Visit Entry Types for your Section and check the URL for the Entry Type
        $referenceUrlTypeId = 0;
        $categoryFieldId = 0; // Field in entry type for category relation

        $debug = true;
        $debugCategories = false;
        $debugReference = false;
        $debugSubjects = false;
        $debugHowTo = false;
        $debugStatute = false;
        $debugRules = false;
        $debugOtherRules = false;
        $debugNews = false;
        $save = true;

        $run_categories = false;
        $run_reference_materials = false;
        $run_subjects = false;
        $run_howto = false;
        $run_statute = false;
        $run_rules = false;
        $run_other_rules = false;

        $run_subjects_redline = false;
        $run_subjects_blueline = false;
        $run_subjects_orangeline = false;

        $run_howto_redline = false;
        $run_howto_greenline = false;
        $run_howto_orangeline = false;

        $run_statute_redline = false;
        $run_statute_greenline = false;
        $run_statute_blueline = false;

        $run_rules_redline = false;
        $run_rules_greenline = false;
        $run_rules_blueline = false;

        $run_other_rules_redline = false;
        $run_other_rules_greenline = false;
        $run_other_rules_blueline = false;

        $run_news = false;

        $run_reference_default_investment_advisers = false;

        if ($run_reference_default_investment_advisers) {
            $entries = craft()->elements->getCriteria(ElementType::Entry);
            $entries->section = 'referenceMaterials';
            //$entries->limit = 1;

            $existingEntries = $entries->find();

            foreach ($existingEntries as $existingEntry) {
                echo 'id: ' . $existingEntry->id . '<br />';

                $entryToSave = new EntryModel();

                $entryToSave = $existingEntry;

                // Setting these in case they're required to be set for saveEntry()
                $entryToSave->sectionId = 9;
                $entryToSave->typeId = 9;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                $entryToSave->getContent()->title = $existingEntry->title;

                $entryToSave->getContent()->showInInvestmentAdvisersReference = 1;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    echo 'saved<br />';
                }
                else {
                    echo 'not saved<br />';
                    var_dump( $entryToSave->getAllErrors() );
                    //echo implode(', ', $entryToSave()->getAllErrors() );

                    // RESTORE REQUIRED STATUS on Document ID, Reference Item Name, Files
                }
            }
        }

        if ($run_categories) {
            // Let's import all the Reference Material categories FIRST, and capture their legacy ID
            foreach ($categoriesXml->reference_categories[0]->category as $importCategory) {
                //var_dump($importCategory);
                if ($debugCategories)
                {
                    // Imported/feed category details display
                    echo 'id: ' . $importCategory->id . '<br />';
                    echo 'parent id: ' . $importCategory->parent_id . '<br />';
                    echo 'url title/slug: ' . $importCategory->category_url_title . '<br />';
                    echo $importCategory->name . '<br />';
                }

                // Let's see if this category exists or not...
                $categories = craft()->elements->getCriteria(ElementType::Category);
                $categories->groupId = 1;
                $categories->legacyId = $importCategory->id;
                $categories->limit = 1;
                $category = $categories->find();

                $categoryToSave = new CategoryModel();
                $categoryToSave->groupId = 1;
                $categoryToSave->enabled = true;

                // Did we find a match?
                if (count($category) == 1)
                {
                    foreach ($category as $existingCategory)
                    {
                        // Use the existing category instead of saving a new category
                        $categoryToSave = $existingCategory;
                        if ($debugCategories) 'We have an existing category to update.<br />';
                    }
                }

                // If parent_id == 0, omit setting parent
                if ($importCategory->parent_id != 0)
                {
                    if ($debugCategories) echo 'We have a parent category!<br />';
                    $parentCategories = craft()->elements->getCriteria(ElementType::Category);
                    $parentCategories->groupId = 1;
                    $parentCategories->legacyId = $importCategory->parent_id;
                    $parentCategories->limit = 1;
                    $parentCategory = $parentCategories->find();
                    // We are assuming that the source feed outputs the nested categories in order, so the
                    // parent category should already exist. I.e., we don't need to create it if not found.
                    if (count($parentCategory) == 1)
                    {
                        foreach ($parentCategory as $existingParentCategory)
                        {
                            if ($debugCategories) echo 'Craft parent ID: ' . $existingParentCategory->id . '<br />';
                            $categoryToSave->newParentId = $existingParentCategory->id;
                            $categoryToSave->setParent($existingParentCategory);
                        }
                    }
                }
                $categoryToSave->slug = $importCategory->category_url_title;
                $categoryToSave->getContent()->title = $importCategory->name;
                $categoryToSave->getContent()->legacyId = $importCategory->id;
                // This will automatically save the category if it does not already exist
                // We've either set the attributes of a new categories, or updated the attributes of a found category
                craft()->categories->saveCategory($categoryToSave);
                //$saveCategory = true;
                if ($debugCategories)
                    echo '<br />';
            }
        }

        // Then we can just synch each Reference Material entry's applied categories by ID, without dealing with parent categories and matches in this sitepoint
        if ($run_reference_materials) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $referenceMaterialsXml = simplexml_load_file('http://40act.craft.dev:8888/reference-materials.xml'); // Replace with Reference Materials feed
            $sectionId = 9; // Visit settings for your Section and check the URL
            $typeId = 9; // Visit Entry Types for your Section and check the URL for the Entry Type
            $referenceUrlTypeId = 10;
            $categoryFieldId = 0; // Field in entry type for category relation

            foreach ($referenceMaterialsXml->reference_materials[0]->item as $importItem) {
                // Note that the URL field has not been used on the EE site, so we're not going to import the URL entry type (10)
                $referenceItem = array();
                $referenceItem['entry_id'] = (string)$importItem ->id;
                // x $entryUrlTitle
                $referenceItem['slug'] = (string)$importItem->slug;
                // x $entryDate
                $referenceItem['entryDate'] = $importItem->entry_date;
                // x $entryStatus
                $referenceItem['entryStatus'] = (string)$importItem->status;
                // x $entryTitle
                $referenceItem['entryTitle'] = (string)$importItem->title;
                // x $entryDocumentId
                $referenceItem['entryDocumentId'] = (string)$importItem->document_id;
                // x $entryItemName
                $referenceItem['entryItemName'] = (string)$importItem->item_name;
                // x $entryPubDate
                $referenceItem['pubDate'] = $importItem->pub_date;
                // x $entryFiles – Need to loop through and look up assets
                //               Also need to load asset fields – Title, caption, others?
                $filesArray = array();
                $assetsToSave = array();
                foreach ($importItem->files->file as $referenceFile) {
                    $assets = craft()->elements->getCriteria(ElementType::Asset);
                    $assets->filename = $referenceFile->filename;
                    $assets->limit = 1;
                    $assetId = 0;
                    foreach ($assets as $assetMatched)
                        $assetId = $assetMatched->id;
                    $filesArray[] = array (
                        $referenceFile->title,
                        $referenceFile->filename,
                        $assetId,
                    );
                    $assetsToSave[] = $assetId;
                }
                $saveAssets = false;
                if ( count($assetsToSave) > 0 )
                    $saveAssets = true;
                $referenceItem['entryFiles'] = $filesArray;
                // x $entrySource – Simply load text value into plaintext field
                $referenceItem['entrySource'] = $importItem->source;
                // x $entryDescription – Rich text
                $referenceItem['entryDescription'] = $importItem->description;
                // x $entryContent – Rich text
                $referenceItem['entryContent'] = $importItem->content;

                // x $entryCategories – For each category's legacy ID, create/save a category link to an already imported entry based on legacy ID
                $categoriesLegacyArray = array();
                $categoriesToSave = array();
                foreach ($importItem->categories->category_id as $categoryId) {
                    $categoriesLegacyArray[] = $categoryId;
                    $categories = craft()->elements->getCriteria(ElementType::Category);
                    $categories->groupId = 1;
                    $categories->legacyId = (string)$categoryId;
                    $categories->limit = 1;
                    $category = $categories->first();
                    // There should always be a match
                    if ( count($category) > 0 )
                        $categoriesToSave[] = $category->id;
                }
                $referenceItem['categoryLegacyIds'] = $categoriesLegacyArray;
                $referenceItem['categoryCraftIds'] = $categoriesToSave;
                // But we don't want to set an empty array in our entry save.
                $saveCategory = false;
                if ( count($categoriesToSave) > 0 )
                    $saveCategory = true;

                // x $entryTags
                $tagsArray = array();
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $referenceItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugReference) {
                    echo '<br />***<br /><br />';
                    foreach ($referenceItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($referenceItem[$key])) {
                            echo '<br />';
                            foreach ($referenceItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $referenceItem[$key] . '<br />';
                    }
                }

                // Default page protected to Yes

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'referenceMaterials';
                $entry->limit = 1;
                $entry->slug = (string)$referenceItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $referenceItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$referenceItem['entryDate']);
                $entryToSave->slug = $referenceItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $referenceItem['entryTitle'],
                    'referenceItemDocumentId' => $referenceItem['entryDocumentId'],
                    'referenceItemName' => $referenceItem['entryItemName'],
                    'referenceItemSource' => $referenceItem['entrySource'],
                    'referenceItemPubDate' =>  date('Y-m-d h:m:s', (float)$referenceItem['pubDate']),
                    'referenceItemDescription' => $referenceItem['entryDescription'],
                    'referenceItemContent' => $referenceItem['entryContent'],
                    'isProtected' => 1,
                    'legacyId' => $referenceItem['entry_id'],
                ));
                if ($saveAssets)
                    $entryToSave->getContent()->referenceItemFiles = $assetsToSave;

                if ($saveCategory)
                    $entryToSave->getContent()->referenceItemCategories = $categoriesToSave;

                if ($saveTags)
                    $entryToSave->getContent()->referenceItemTags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }

            }
        }

        if ($run_subjects) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $subjectsXml = simplexml_load_file('http://40act.craft.dev:8888/subjects.xml');
            $sectionId = 3; // Visit settings for your Section and check the URL
            $typeId = 3; // Visit Entry Types for your Section and check the URL for the Entry Type

            foreach ($subjectsXml->subjects[0]->item as $importItem) {
                $subjectItem = array();
                // Need to load as legacy ID for parent ID lookups, re-import (though reference re-import was based on slug)
                $subjectItem['entry_id'] = (string)$importItem ->id;
                // $entryUrlTitle
                $subjectItem['slug'] = (string)$importItem->slug;
                // $entryDate
                $subjectItem['entryDate'] = $importItem->entry_date;
                // $entryStatus
                $subjectItem['entryStatus'] = (string)$importItem->status;
                // $entryTitle
                $subjectItem['entryTitle'] = (string)$importItem->title;
                // $entryContent – Rich text
                $subjectItem['entryContent'] = $importItem->content;
                // tags
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $subjectItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugSubjects) {
                    echo '<br />***<br /><br />';
                    foreach ($subjectItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($subjectItem[$key])) {
                            echo '<br />';
                            foreach ($subjectItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $subjectItem[$key] . '<br />';
                    }
                }

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'investmentAdvisersSubjects';
                $entry->limit = 1;
                $entry->slug = (string)$subjectItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                // If parent_id == 0, omit setting parent
                if ($importItem->parent_id != 276) // Top-level parent node for Investment Advisor Subjects
                {
                    if ($debugSubjects) echo 'We have a parent entry!<br />';
                    $parentEntries = craft()->elements->getCriteria(ElementType::Entry);
                    $parentEntries->section = 'investmentAdvisersSubjects';
                    $parentEntries->legacyId = $importItem->parent_id;
                    $parentEntries->limit = 1;
                    $parentEntry = $parentEntries->find();
                    // We are assuming that the source feed outputs the nested categories in order, so the
                    // parent category should already exist. I.e., we don't need to create it if not found.
                    if (count($parentEntry) == 1)
                    {
                        foreach ($parentEntry as $existingParentEntry)
                        {
                            if ($debugSubjects) echo 'Craft parent ID: ' . $existingParentEntry->id . '<br />';
                            $entryToSave->parentId = $existingParentEntry->id;
                            $entryToSave->setParent($existingParentEntry);
                        }
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $subjectItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$subjectItem['entryDate']);
                $entryToSave->slug = $subjectItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $subjectItem['entryTitle'],
                    'legacyId' => $subjectItem['entry_id'],
                    'pageContent' => $subjectItem['entryContent'],
                    'isProtected' => 1,
                ));

                if ($saveTags)
                    $entryToSave->getContent()->pageTags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }
            }

            if ($run_subjects_redline)
            {
                foreach ($subjectsXml->subjects[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersSubjects';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;
                    //$entry->slug = $importItem->slug;
                    /*$entry->getContent()->setAttributes(array(
                        'legacyId' => (string)$importItem->id,
                    ));*/

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Treatise entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->redline->redline_item as $item) {
                        $referenceEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $referenceEntry->limit = 1;
                        $referenceEntry->legacyId = (string)$item->reference_item->reference_item_legacy_id;
                        /*$referenceEntry->getContent()->setAttributes(array(
                            'legacyId' => (string)$item->reference_item->reference_item_legacy_id,
                        ));*/

                        $entries = $referenceEntry->find();
                        if (count($entries) == 0) {
                            echo 'Reference Item entry not found! / EE ID: ' . (string)$item->reference_item->reference_item_legacy_id . '<br />';
                            continue;
                        }

                        $existingReferenceEntry = $referenceEntry->first();

                        echo 'Matrix: Reference Entry ID: ' . $existingReferenceEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 48;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 1;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingReferenceEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_subjects_blueline)
            {
                foreach ($subjectsXml->subjects[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersSubjects';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Treatise entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->blueline->blueline_item as $item) {
                        $howtoEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $howtoEntry->limit = 1;
                        $howtoEntry->legacyId = (string)$item->legacy_id;

                        $entries = $howtoEntry->find();
                        if (count($entries) == 0) {
                            echo 'How-To entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingHowToEntry = $howtoEntry->first();

                        echo 'Matrix: How-To Entry ID: ' . $existingHowToEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 54;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 3;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingHowToEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        craft()->matrix->saveBlock($block);
                    }
                }
            }

            if ($run_subjects_orangeline)
            {
                foreach ($subjectsXml->subjects[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersSubjects';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Treatise entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->orangeline->orangeline_item as $item) {
                        $lawsrulesEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $lawsrulesEntry->limit = 1;
                        $lawsrulesEntry->legacyId = (string)$item->legacy_id;

                        $entries = $lawsrulesEntry->find();
                        if (count($entries) == 0) {
                            echo 'Laws & Rules entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingLawsRulesEntry = $lawsrulesEntry->first();

                        echo 'Matrix: Laws & Rules Entry ID: ' . $existingLawsRulesEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 57;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 4;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingLawsRulesEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        craft()->matrix->saveBlock($block);
                    }
                }
            }
        }

        if ($run_howto) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $howtoXml = simplexml_load_file('http://40act.craft.dev:8888/how-to.xml');
            $sectionId = 4; // Visit settings for your Section and check the URL
            $typeId = 4; // Visit Entry Types for your Section and check the URL for the Entry Type

            foreach ($howtoXml->entries[0]->item as $importItem) {
                $howtoItem = array();
                // Need to load as legacy ID for parent ID lookups, re-import (though reference re-import was based on slug)
                $howtoItem['entry_id'] = (string)$importItem ->id;
                // $entryUrlTitle
                $howtoItem['slug'] = (string)$importItem->slug;
                // $entryDate
                $howtoItem['entryDate'] = $importItem->entry_date;
                // $entryStatus
                $howtoItem['entryStatus'] = (string)$importItem->status;
                // $entryTitle
                $howtoItem['entryTitle'] = (string)$importItem->title;
                // $entryContent – Rich text
                $howtoItem['entryContent'] = $importItem->content;
                // tags
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $howtoItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugHowTo) {
                    echo '<br />***<br /><br />';
                    foreach ($howtoItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($howtoItem[$key])) {
                            echo '<br />';
                            foreach ($howtoItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $howtoItem[$key] . '<br />';
                    }
                }

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'investmentAdvisersHowTo';
                $entry->limit = 1;
                $entry->slug = (string)$howtoItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                // If parent_id == 0, omit setting parent
                if ($importItem->parent_id != 277) // Top-level parent node for Investment Advisor How-To
                {
                    if ($debugHowTo) echo 'We have a parent entry!<br />';
                    $parentEntries = craft()->elements->getCriteria(ElementType::Entry);
                    $parentEntries->section = 'investmentAdvisersHowTo';
                    $parentEntries->legacyId = $importItem->parent_id;
                    $parentEntries->limit = 1;
                    $parentEntry = $parentEntries->find();
                    // We are assuming that the source feed outputs the nested categories in order, so the
                    // parent category should already exist. I.e., we don't need to create it if not found.
                    if (count($parentEntry) == 1)
                    {
                        foreach ($parentEntry as $existingParentEntry)
                        {
                            if ($debugHowTo) echo 'Craft parent ID: ' . $existingParentEntry->id . '<br />';
                            $entryToSave->parentId = $existingParentEntry->id;
                            $entryToSave->setParent($existingParentEntry);
                        }
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $howtoItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$howtoItem['entryDate']);
                $entryToSave->slug = $howtoItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $howtoItem['entryTitle'],
                    'legacyId' => $howtoItem['entry_id'],
                    'pageContent' => $howtoItem['entryContent'],
                    'isProtected' => 1,
                ));

                if ($saveTags)
                    $entryToSave->getContent()->pageTags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }
            }
            if ($run_howto_redline)
            {
                foreach ($howtoXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersHowTo';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;
                    //$entry->slug = $importItem->slug;
                    /*$entry->getContent()->setAttributes(array(
                        'legacyId' => (string)$importItem->id,
                    ));*/

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'How-To entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->redline->redline_item as $item) {
                        $referenceEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $referenceEntry->limit = 1;
                        $referenceEntry->legacyId = (string)$item->reference_item->reference_item_legacy_id;
                        /*$referenceEntry->getContent()->setAttributes(array(
                            'legacyId' => (string)$item->reference_item->reference_item_legacy_id,
                        ));*/

                        $entries = $referenceEntry->find();
                        if (count($entries) == 0) {
                            echo 'Reference Item entry not found! / EE ID: ' . (string)$item->reference_item->reference_item_legacy_id . '<br />';
                            continue;
                        }

                        $existingReferenceEntry = $referenceEntry->first();

                        echo 'Matrix: Reference Entry ID: ' . $existingReferenceEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 48;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 1;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingReferenceEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_howto_greenline)
            {
                foreach ($howtoXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersHowTo';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'How-To entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->greenline->greenline_item as $item) {
                        $subjectsEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $subjectsEntry->limit = 1;
                        $subjectsEntry->legacyId = (string)$item->legacy_id;

                        $entries = $subjectsEntry->find();
                        if (count($entries) == 0) {
                            echo 'Treatise entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingSubjectsEntry = $subjectsEntry->first();

                        echo 'Matrix: Treatise Entry ID: ' . $existingSubjectsEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 51;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 2;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingSubjectsEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        craft()->matrix->saveBlock($block);
                    }
                }
            }

            if ($run_howto_orangeline)
            {
                foreach ($howtoXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersHowTo';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'How-To entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->orangeline->orangeline_item as $item) {
                        $lawsrulesEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $lawsrulesEntry->limit = 1;
                        $lawsrulesEntry->legacyId = (string)$item->legacy_id;

                        $entries = $lawsrulesEntry->find();
                        if (count($entries) == 0) {
                            echo 'Laws & Rules entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingLawsRulesEntry = $lawsrulesEntry->first();

                        echo 'Matrix: Laws & Rules Entry ID: ' . $existingLawsRulesEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 57;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 4;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingLawsRulesEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        craft()->matrix->saveBlock($block);
                    }
                }
            }
        }

        if ($run_statute) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $statuteXml = simplexml_load_file('http://40act.craft.dev:8888/statute.xml');
            $sectionId = 6; // Visit settings for your Section and check the URL
            $typeId = 6; // Visit Entry Types for your Section and check the URL for the Entry Type

            foreach ($statuteXml->entries[0]->item as $importItem) {
                $statuteItem = array();
                // Need to load as legacy ID for parent ID lookups, re-import (though reference re-import was based on slug)
                $statuteItem['entry_id'] = (string)$importItem ->id;
                // $entryUrlTitle
                $statuteItem['slug'] = (string)$importItem->slug;
                // $entryDate
                $statuteItem['entryDate'] = $importItem->entry_date;
                // $entryStatus
                $statuteItem['entryStatus'] = (string)$importItem->status;
                // $entryTitle
                $statuteItem['entryTitle'] = (string)$importItem->title;
                // $entryContent – Rich text
                $statuteItem['entryContent'] = $importItem->content;
                // tags
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $statuteItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugStatute) {
                    echo '<br />***<br /><br />';
                    foreach ($statuteItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($statuteItem[$key])) {
                            echo '<br />';
                            foreach ($statuteItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $statuteItem[$key] . '<br />';
                    }
                }

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'investmentAdvisersActOf1940Statute';
                $entry->limit = 1;
                $entry->slug = (string)$statuteItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                // If parent_id == 0, omit setting parent
                if ($importItem->parent_id != 399) // Top-level parent node for Investment Advisor How-To
                {
                    if ($debugStatute) echo 'We have a parent entry!<br />';
                    $parentEntries = craft()->elements->getCriteria(ElementType::Entry);
                    $parentEntries->section = 'investmentAdvisersActOf1940Statute';
                    $parentEntries->legacyId = $importItem->parent_id;
                    $parentEntries->limit = 1;
                    $parentEntry = $parentEntries->find();
                    // We are assuming that the source feed outputs the nested categories in order, so the
                    // parent category should already exist. I.e., we don't need to create it if not found.
                    if (count($parentEntry) == 1)
                    {
                        foreach ($parentEntry as $existingParentEntry)
                        {
                            if ($debugStatute) echo 'Craft parent ID: ' . $existingParentEntry->id . '<br />';
                            $entryToSave->parentId = $existingParentEntry->id;
                            $entryToSave->setParent($existingParentEntry);
                        }
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $statuteItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$statuteItem['entryDate']);
                $entryToSave->slug = $statuteItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $statuteItem['entryTitle'],
                    'legacyId' => $statuteItem['entry_id'],
                    'pageContent' => $statuteItem['entryContent'],
                    'isProtected' => 0,
                ));

                if ($saveTags)
                    $entryToSave->getContent()->pageTags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }
            }
            if ($run_statute_redline)
            {
                foreach ($statuteXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersActOf1940Statute';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;
                    //$entry->slug = $importItem->slug;
                    /*$entry->getContent()->setAttributes(array(
                        'legacyId' => (string)$importItem->id,
                    ));*/

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Statute entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->redline->redline_item as $item) {
                        $referenceEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $referenceEntry->limit = 1;
                        $referenceEntry->legacyId = (string)$item->reference_item->reference_item_legacy_id;
                        /*$referenceEntry->getContent()->setAttributes(array(
                            'legacyId' => (string)$item->reference_item->reference_item_legacy_id,
                        ));*/

                        $entries = $referenceEntry->find();
                        if (count($entries) == 0) {
                            echo 'Reference Item entry not found! / EE ID: ' . (string)$item->reference_item->reference_item_legacy_id . '<br />';
                            continue;
                        }

                        $existingReferenceEntry = $referenceEntry->first();

                        echo 'Matrix: Reference Entry ID: ' . $existingReferenceEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 48;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 1;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingReferenceEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_statute_greenline)
            {
                foreach ($statuteXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersActOf1940Statute';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Statute entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->greenline->greenline_item as $item) {
                        $subjectsEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $subjectsEntry->limit = 1;
                        $subjectsEntry->legacyId = (string)$item->legacy_id;

                        $entries = $subjectsEntry->find();
                        if (count($entries) == 0) {
                            echo 'Treatise entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingSubjectsEntry = $subjectsEntry->first();

                        echo 'Matrix: Treatise Entry ID: ' . $existingSubjectsEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 51;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 2;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingSubjectsEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_statute_blueline)
            {
                foreach ($statuteXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersActOf1940Statute';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Statute entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->blueline->blueline_item as $item) {
                        $howtoEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $howtoEntry->limit = 1;
                        $howtoEntry->legacyId = (string)$item->legacy_id;

                        $entries = $howtoEntry->find();
                        if (count($entries) == 0) {
                            echo 'How-To entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingHowToEntry = $howtoEntry->first();

                        echo 'Matrix: How-To Entry ID: ' . $existingHowToEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 54;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 3;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingHowToEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
        }

        if ($run_rules) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $rulesXml = simplexml_load_file('http://40act.craft.dev:8888/rules.xml');
            $sectionId = 7; // Visit settings for your Section and check the URL
            $typeId = 7; // Visit Entry Types for your Section and check the URL for the Entry Type

            foreach ($rulesXml->entries[0]->item as $importItem) {
                $rulesItem = array();
                // Need to load as legacy ID for parent ID lookups, re-import (though reference re-import was based on slug)
                $rulesItem['entry_id'] = (string)$importItem ->id;
                // $entryUrlTitle
                $rulesItem['slug'] = (string)$importItem->slug;
                // $entryDate
                $rulesItem['entryDate'] = $importItem->entry_date;
                // $entryStatus
                $rulesItem['entryStatus'] = (string)$importItem->status;
                // $entryTitle
                $rulesItem['entryTitle'] = (string)$importItem->title;
                // $entryContent – Rich text
                $rulesItem['entryContent'] = $importItem->content;
                // tags
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $rulesItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugRules) {
                    echo '<br />***<br /><br />';
                    foreach ($rulesItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($rulesItem[$key])) {
                            echo '<br />';
                            foreach ($rulesItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $rulesItem[$key] . '<br />';
                    }
                }

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'investmentAdvisersActOf1940Rules';
                $entry->limit = 1;
                $entry->slug = (string)$rulesItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                // If parent_id == 0, omit setting parent
                if ($importItem->parent_id != 401) // Top-level parent node for Investment Advisor How-To
                {
                    if ($debugRules) echo 'We have a parent entry!<br />';
                    $parentEntries = craft()->elements->getCriteria(ElementType::Entry);
                    $parentEntries->section = 'investmentAdvisersActOf1940Rules';
                    $parentEntries->legacyId = $importItem->parent_id;
                    $parentEntries->limit = 1;
                    $parentEntry = $parentEntries->find();
                    // We are assuming that the source feed outputs the nested categories in order, so the
                    // parent category should already exist. I.e., we don't need to create it if not found.
                    if (count($parentEntry) == 1)
                    {
                        foreach ($parentEntry as $existingParentEntry)
                        {
                            if ($debugRules) echo 'Craft parent ID: ' . $existingParentEntry->id . '<br />';
                            $entryToSave->parentId = $existingParentEntry->id;
                            $entryToSave->setParent($existingParentEntry);
                        }
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $rulesItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$rulesItem['entryDate']);
                $entryToSave->slug = $rulesItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $rulesItem['entryTitle'],
                    'legacyId' => $rulesItem['entry_id'],
                    'pageContent' => $rulesItem['entryContent'],
                    'isProtected' => 0,
                ));

                if ($saveTags)
                    $entryToSave->getContent()->pageTags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }
            }
            if ($run_rules_redline)
            {
                foreach ($rulesXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersActOf1940Rules';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;
                    //$entry->slug = $importItem->slug;
                    /*$entry->getContent()->setAttributes(array(
                        'legacyId' => (string)$importItem->id,
                    ));*/

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Rules entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->redline->redline_item as $item) {
                        $referenceEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $referenceEntry->limit = 1;
                        $referenceEntry->legacyId = (string)$item->reference_item->reference_item_legacy_id;
                        /*$referenceEntry->getContent()->setAttributes(array(
                            'legacyId' => (string)$item->reference_item->reference_item_legacy_id,
                        ));*/

                        $entries = $referenceEntry->find();
                        if (count($entries) == 0) {
                            echo 'Reference Item entry not found! / EE ID: ' . (string)$item->reference_item->reference_item_legacy_id . '<br />';
                            continue;
                        }

                        $existingReferenceEntry = $referenceEntry->first();

                        echo 'Matrix: Reference Entry ID: ' . $existingReferenceEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 48;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 1;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingReferenceEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_rules_greenline)
            {
                foreach ($rulesXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersActOf1940Rules';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Rules entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->greenline->greenline_item as $item) {
                        $subjectsEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $subjectsEntry->limit = 1;
                        $subjectsEntry->legacyId = (string)$item->legacy_id;

                        $entries = $subjectsEntry->find();
                        if (count($entries) == 0) {
                            echo 'Treatise entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingSubjectsEntry = $subjectsEntry->first();

                        echo 'Matrix: Treatise Entry ID: ' . $existingSubjectsEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 51;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 2;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingSubjectsEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_rules_blueline)
            {
                foreach ($rulesXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'investmentAdvisersActOf1940Rules';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Rules entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->blueline->blueline_item as $item) {
                        $howtoEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $howtoEntry->limit = 1;
                        $howtoEntry->legacyId = (string)$item->legacy_id;

                        $entries = $howtoEntry->find();
                        if (count($entries) == 0) {
                            echo 'How-To entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingHowToEntry = $howtoEntry->first();

                        echo 'Matrix: How-To Entry ID: ' . $existingHowToEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 54;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 3;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingHowToEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
        }

        if ($run_other_rules) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $otherRulesXml = simplexml_load_file('http://40act.craft.dev:8888/other-rules.xml');
            $sectionId = 8; // Visit settings for your Section and check the URL
            $typeId = 8; // Visit Entry Types for your Section and check the URL for the Entry Type

            foreach ($otherRulesXml->entries[0]->item as $importItem) {
                $rulesItem = array();
                // Need to load as legacy ID for parent ID lookups, re-import (though reference re-import was based on slug)
                $rulesItem['entry_id'] = (string)$importItem ->id;
                // $entryUrlTitle
                $rulesItem['slug'] = (string)$importItem->slug;
                // $entryDate
                $rulesItem['entryDate'] = $importItem->entry_date;
                // $entryStatus
                $rulesItem['entryStatus'] = (string)$importItem->status;
                // $entryTitle
                $rulesItem['entryTitle'] = (string)$importItem->title;
                // $entryContent – Rich text
                $rulesItem['entryContent'] = $importItem->content;
                // tags
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $rulesItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugOtherRules) {
                    echo '<br />***<br /><br />';
                    foreach ($rulesItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($rulesItem[$key])) {
                            echo '<br />';
                            foreach ($rulesItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $rulesItem[$key] . '<br />';
                    }
                }

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'otherLawsRules';
                $entry->limit = 1;
                $entry->slug = (string)$rulesItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                // If parent_id == 0, omit setting parent
                if ($importItem->parent_id != 401) // Top-level parent node for Investment Advisor How-To
                {
                    if ($debugOtherRules) echo 'We have a parent entry!<br />';
                    $parentEntries = craft()->elements->getCriteria(ElementType::Entry);
                    $parentEntries->section = 'otherLawsRules';
                    $parentEntries->legacyId = $importItem->parent_id;
                    $parentEntries->limit = 1;
                    $parentEntry = $parentEntries->find();
                    // We are assuming that the source feed outputs the nested categories in order, so the
                    // parent category should already exist. I.e., we don't need to create it if not found.
                    if (count($parentEntry) == 1)
                    {
                        foreach ($parentEntry as $existingParentEntry)
                        {
                            if ($debugOtherRules) echo 'Craft parent ID: ' . $existingParentEntry->id . '<br />';
                            $entryToSave->parentId = $existingParentEntry->id;
                            $entryToSave->setParent($existingParentEntry);
                        }
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $rulesItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$rulesItem['entryDate']);
                $entryToSave->slug = $rulesItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $rulesItem['entryTitle'],
                    'legacyId' => $rulesItem['entry_id'],
                    'pageContent' => $rulesItem['entryContent'],
                    'isProtected' => 0,
                ));

                if ($saveTags)
                    $entryToSave->getContent()->pageTags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }
            }
            if ($run_other_rules_redline)
            {
                foreach ($otherRulesXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'otherLawsRules';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;
                    //$entry->slug = $importItem->slug;
                    /*$entry->getContent()->setAttributes(array(
                        'legacyId' => (string)$importItem->id,
                    ));*/

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Other Rules entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->redline->redline_item as $item) {
                        $referenceEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $referenceEntry->limit = 1;
                        $referenceEntry->legacyId = (string)$item->reference_item->reference_item_legacy_id;
                        /*$referenceEntry->getContent()->setAttributes(array(
                            'legacyId' => (string)$item->reference_item->reference_item_legacy_id,
                        ));*/

                        $entries = $referenceEntry->find();
                        if (count($entries) == 0) {
                            echo 'Reference Item entry not found! / EE ID: ' . (string)$item->reference_item->reference_item_legacy_id . '<br />';
                            continue;
                        }

                        $existingReferenceEntry = $referenceEntry->first();

                        echo 'Matrix: Reference Entry ID: ' . $existingReferenceEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 48;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 1;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingReferenceEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_other_rules_greenline)
            {
                foreach ($otherRulesXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'otherLawsRules';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Other Rules entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->greenline->greenline_item as $item) {
                        $subjectsEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $subjectsEntry->limit = 1;
                        $subjectsEntry->legacyId = (string)$item->legacy_id;

                        $entries = $subjectsEntry->find();
                        if (count($entries) == 0) {
                            echo 'Treatise entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingSubjectsEntry = $subjectsEntry->first();

                        echo 'Matrix: Treatise Entry ID: ' . $existingSubjectsEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 51;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 2;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingSubjectsEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
            if ($run_other_rules_blueline)
            {
                foreach ($otherRulesXml->entries[0]->item as $importItem) {
                    $entry = craft()->elements->getCriteria(ElementType::Entry);
                    $entry->section = 'otherLawsRules';
                    $entry->limit = 1;
                    $entry->legacyId = (string)$importItem->id;

                    echo '<br /><br />*****<br />';
                    $entries = $entry->find();
                    if (count($entries) == 0) {
                        echo 'Other Rules entry not found! / EE ID: ' . $importItem->id . '<br />';
                        continue;
                    }
                    echo (string)$importItem->id . '<br />';
                    $existingEntry = $entry->first();
                    echo 'Matrix: Entry ID: ' . $existingEntry->id . '<br />';

                    foreach ($importItem->blueline->blueline_item as $item) {
                        $howtoEntry = craft()->elements->getCriteria(ElementType::Entry);
                        $howtoEntry->limit = 1;
                        $howtoEntry->legacyId = (string)$item->legacy_id;

                        $entries = $howtoEntry->find();
                        if (count($entries) == 0) {
                            echo 'How-To entry not found! / EE ID: ' . (string)$item->legacy_id . '<br />';
                            continue;
                        }

                        $existingHowToEntry = $howtoEntry->first();

                        echo 'Matrix: How-To Entry ID: ' . $existingHowToEntry->id . '<br />';

                        $block = new MatrixBlockModel();
                        $block->fieldId = 54;
                        $block->ownerId = $existingEntry->id;
                        $block->typeId = 3;
                        $block->getContent()->setAttributes(array(
                            'entry' => [$existingHowToEntry->id],
                            'annotation' => (string)$item->annotation,
                        ));
                        //craft()->matrix->saveBlock($block);
                    }
                }
            }
        }

        if ($run_news) {
            // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
            $newsXml = simplexml_load_file('http://40act.craft.dev:8888/news.xml'); // Replace with Reference Materials feed
            $sectionId = 2; // Visit settings for your Section and check the URL
            $typeId = 2; // Visit Entry Types for your Section and check the URL for the Entry Type

            foreach ($newsXml->news[0]->article as $importItem) {
                // Note that the URL field has not been used on the EE site, so we're not going to import the URL entry type (10)
                $newsItem = array();
                $newsItem['entry_id'] = (string)$importItem ->id;
                $newsItem['slug'] = (string)$importItem->slug;
                $newsItem['entryDate'] = $importItem->entry_date;
                $newsItem['entryStatus'] = (string)$importItem->status;
                $newsItem['entryTitle'] = (string)$importItem->title;
                $newsItem['newsUrl'] = (string)$importItem->url;
                $newsItem['newsBlurb'] = (string)$importItem->blurb;
                $newsItem['newsArticle'] = (string)$importItem->article;

                $tagsArray = array();
                $tagsToSave = array();
                foreach ($importItem->tags->tag as $tag) {
                    // Need to look up / create tag if it does not exist
                    $tagToFind = craft()->elements->getCriteria(ElementType::Tag);
                    $tagToFind->title = (string)$tag;
                    $tagToFind->limit = 1;
                    $tagToFind->groupId = $tagSetId;
                    $existingTag = $tagToFind->find();
                    if (count($existingTag) == 0) {
                        $tagToSave = new TagModel();
                        $tagToSave->groupId = $tagSetId;
                        $tagToSave->getContent()->setAttributes(array(
                            'title' => (string)$tag));
                        craft()->tags->saveTag($tagToSave);
                    }
                    $tagToSave = $tagToFind->first();
                    $tagsArray[] = array(
                        $tag,
                        $tagToSave->id,
                    );
                    $tagsToSave[] = $tagToSave->id;
                }
                $newsItem['tags'] = $tagsToSave;
                $saveTags = false;
                if ( count($tagsToSave) > 0 )
                    $saveTags = true;

                if ($debugNews) {
                    echo '<br />***<br /><br />';
                    foreach ($newsItem as $key => $value) {
                        echo $key . ': ';
                        if (is_array($newsItem[$key])) {
                            echo '<br />';
                            foreach ($newsItem[$key] as $arrayItem) {
                                if (is_array($arrayItem)) {
                                    foreach ($arrayItem as $subArrayItem) {
                                        echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $subArrayItem . '<br />';
                                    }
                                }
                                else
                                    echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $arrayItem . '<br />';
                            }
                        }
                        else
                            echo $newsItem[$key] . '<br />';
                    }
                }

                // Default page protected to Yes

                // Save the entry
                if (!$save)
                    continue;

                $entry = craft()->elements->getCriteria(ElementType::Entry);
                $entry->section = 'news';
                $entry->limit = 1;
                $entry->slug = (string)$newsItem['slug'];

                $existingEntry = $entry->find();

                $entryToSave = new EntryModel();

                if (count($existingEntry > 0))
                {
                    foreach ($existingEntry as $existingEntry)
                    {
                        $entryToSave = $existingEntry;
                    }
                }

                $entryToSave->sectionId = $sectionId;
                $entryToSave->typeId = $typeId;
                $entryToSave->authorId = 1;
                $entryToSave->enabled = true;
                if ( $newsItem['entryStatus'] == 'closed' )
                    $entryToSave->enabled = false;
                $entryToSave->postDate = date('Y-m-d h:m:s', (float)$newsItem['entryDate']);
                $entryToSave->slug = $newsItem['slug'];
                $entryToSave->getContent()->setAttributes(array(
                    // Title, fields go here
                    'title' => $newsItem['entryTitle'],
                    'newsBlurb' => $newsItem['newsBlurb'],
                    'newsUrl' => $newsItem['newsUrl'],
                    'newsArticle' => $newsItem['newsArticle'],
                    'legacyId' => $newsItem['entry_id'],
                ));

                if ($saveTags)
                    $entryToSave->getContent()->tags = $tagsToSave;

                if ( craft()->entries->saveEntry($entryToSave) )
                {
                    $totalPosts++;
                    $retVal = $totalPosts;
                    continue;
                }
                else
                {
                    $retVal = false;
                }

            }
        }

        if ($debug)
            exit;
        return $retVal;
    }
}
