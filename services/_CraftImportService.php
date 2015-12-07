<?php

namespace Craft;

class CraftImportService extends BaseApplicationComponent
{
    public function __construct()
    {

    }

    public function loadEntries()
    {
        $totalPosts = 0;
        $retVal = $totalPosts;

        /* REFERENCE MATERIALS */
        // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
        $categoriesXml = simplexml_load_file('http://40act.ee.dev:8888/export/categories');
        //echo 'XML loaded<br />';
        $importTags = false;
        // If importing tags, set your Tag Set ID
        $tagSetId = 1;
        $sectionId = 0; // Visit settings for your Section and check the URL
        $typeId = 0; // Visit Entry Types for your Section and check the URL for the Entry Type
        $referenceUrlTypeId = 0;
        $categoryFieldId = 0; // Field in entry type for category relation

        $debug = true;
        $save = true;

        // Let's import all the Reference Material categories FIRST, and capture their legacy ID
        foreach ($categoriesXml->reference_categories[0]->category as $importCategory) {
            //var_dump($importCategory);
            if ($debug)
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
                    if ($debug) 'We have an existing category to update.<br />';
                }
            }

            // If parent_id == 0, omit setting parent
            if ($importCategory->parent_id != 0)
            {
                if ($debug) echo 'We have a parent category!<br />';
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
                        if ($debug) echo 'Craft parent ID: ' . $existingParentCategory->id . '<br />';
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
            if ($debug)
                echo '<br />';
        }

        // Then we can just synch each Reference Material entry's applied categories by ID, without dealing with parent categories and matches in this sitepoint

        // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
        $referenceMaterialsXml = simplexml_load_file('http://40act.ee.dev:8888/export/categories'); // Replace with Reference Materials feed
        $sectionId = 9; // Visit settings for your Section and check the URL
        $typeId = 9; // Visit Entry Types for your Section and check the URL for the Entry Type
        $referenceUrlTypeId = 10;
        $categoryFieldId = 0; // Field in entry type for category relation

        foreach ($referenceMaterialsXml->reference_categories[0]->category as $importItem) {
            // Note that the URL field has not been used on the EE site, so we're not going to import the URL entry type (10)
            // $entryTitle


        }

        if ($debug)
            exit;
        return $retVal;


        // =====================================================================
        // Beyond this point is purely reference
        //





        $updatePeopleEntries = true;

        if ($updatePeopleEntries)
        {
            $peopleMatch = craft()->elements->getCriteria(ElementType::Entry);
            $peopleMatch->section = 'people';
            $peopleMatch->find();
            foreach($peopleMatch as $person)
            {
                $person->getContent()->setAttributes(array(
                    'emailMatch' => trim(strtolower($person->emailAddress['linkText']))
                ));
                craft()->entries->saveEntry($person);
            }
        }

        foreach ($xml->channel[0]->item as $importEntry) {
            // Validate fetch on screen
            // Roll up into 1 string to toggle on/off
            $checkImport = '<hr />';
            $checkImport .= $importEntry->pubDate . '<br />';
            $checkImport .= date('Y-m-d h:m:s', strtotime($importEntry->pubDate)) . '<br />';
            $checkImport .= "<h2>" . $importEntry->title . '</h2>';
            $checkImport .= $importEntry->link . '<br />';
            // http://www.watermark.org/blog/disciples-see-jesuspreschool2015/
            $slugArray = explode('/', $importEntry->link);
            $slug = $slugArray[4];
            $checkImport .= $slug . '<br />';
            $checkImport .= $importEntry->description . '<br />';
            $checkImport .= $importEntry->children("content", true) . '<br />'; // content:encoded (Post)
            $checkImport .= $importEntry->category . '<br />';
            $checkImport .= 'RSS author: ' . $importEntry->children("dc", true) . '<br />'; // dc:creator (Author)
            // ->enclosure // W: Media
            $saveEnclosure = false;
            if (isset($importEntry->enclosure))
            {
                $saveEnclosure = true;
                $checkImport .= 'Enclosure URL: ' . $importEntry->enclosure->attributes()->url . '<br />';
                $checkImport .= 'Enclosure length: ' . $importEntry->enclosure->attributes()->length . '<br />';
                $checkImport .= 'Enclosure type: ' . $importEntry->enclosure->attributes()->type . '<br />';
            }

            $oldUrl = 'http://www.watermark.org/blog/wp-content/uploads';
            $newUrl = 'http://cms-cloud.watermark.org/legacy-blog';
            // Make sure you reference this variable below!
            $post = $importEntry->children("content", true);
            // Make sure you run the string containing a subsequent substring first!
            $post = str_replace($oldUrl, $newUrl, $post);

            // http://www.sitepoint.com/simplexml-and-namespaces/
            $ns_wp = $importEntry->children('http://wordpress.org/export/1.2/');
            // New Legacy Fields
            $imageLegacy = '';
            foreach ($importEntry->image->xpath("url[@size='full']/.") as $imageFull)
            {
                $imageLegacy = str_replace($oldUrl, $newUrl, $imageFull);
            }
            if ($debug) echo 'url[@size=full]: ' . $imageLegacy . '<br />';
            $yoastTitleLegacy = '';
            $yoastDescriptionLegacy = '';
            $disqusThreadIdLegacy = '';
            $enclosureUrl = '';
            $enclosureLength = '';
            $enclosureType = '';

            foreach ( $ns_wp->postmeta as $metaNode ) {
                $postmeta_ns_wp = $metaNode->children('http://wordpress.org/export/1.2/');
                switch ($postmeta_ns_wp->meta_key)
                {
                    case '_yoast_wpseo_title':
                        if ($debug) echo '_yoast_wpseo_title: ' . $postmeta_ns_wp->meta_value . '<br />';
                        $yoastTitleLegacy = $postmeta_ns_wp->meta_value;
                        break;
                    case '_yoast_wpseo_metadesc':
                        if ($debug) echo '_yoast_wpseo_metadesc: ' . $postmeta_ns_wp->meta_value . '<br />';
                        $yoastDescriptionLegacy = $postmeta_ns_wp->meta_value;
                        break;
                    case 'dsq_thread_id':
                        if ($debug) echo 'dsq_thread_id: ' . $postmeta_ns_wp->meta_value . '<br />';
                        $disqusThreadIdLegacy = $postmeta_ns_wp->meta_value;
                        break;
                    case 'enclosure':
                        /*$saveEnclosure = true;
                        $enclosure = explode("\n", $postmeta_ns_wp->meta_value);
                        $enclosureUrl = $enclosure[0];
                        $enclosureLength = $enclosure[1];
                        $enclosureType = $enclosure[2];
                        if ($debug) echo 'Enclosure URL: ' . $enclosureUrl . '<br />';
                        if ($debug) echo 'Enclosure length: ' . $enclosureLength . '<br />';
                        if ($debug) echo 'Enclosure type: ' . $enclosureType . '<br />';*/
                        break;
                }
            }
            if (!$save)
                continue;

            //var_dump(trim(strtolower($importEntry->author_email)));
            if ($debug) echo "<br /><b>Incoming Author Email:</b> " . trim(strtolower($importEntry->author_email)) . "<br />";
            $saveAuthor = false;
            $authorToSave = array();
            $people = craft()->elements->getCriteria(ElementType::Entry);
            $people->section = 'people';
            $people->limit = 1;
            //$people->title = $importEntry->children("dc", true);
            $people->emailMatch = trim(strtolower($importEntry->author_email));
            $peopleAuthor = $people->find();
            foreach($peopleAuthor as $person)
            {
                if ($debug) echo "<b>Matched People Entry:</b> " . trim(strtolower($person->emailMatch)) . "<br /><br />";
                $saveAuthor = true;
                array_push($authorToSave, $person->id);
            }

            /*
            $peopleCheck = craft()->elements->getCriteria(ElementType::Entry);
            $peopleCheck->section = 'people';
            $peopleCheck->find();
            foreach($peopleCheck as $person)
            {
                //var_dump( $person->emailAddress['email'] );
                if ( trim(strtolower($importEntry->author_email)) == trim(strtolower($person->emailAddress['email'])) )
                {
                    if ($debug) echo "<b>Matched People Entry:</b> " . trim(strtolower($person->emailAddress['email'])) . "<br /><br />";
                    $saveAuthor = true;
                    array_push($authorToSave, $person->id);
                }
            }
            */

            //echo $checkImport;

            // We can load multiple categories with an array.
            $categoriesToSave = array();
            // But we don't want to set an empty array in our entry save.
            $saveCategory = false;

            foreach ( $importEntry->xpath('category') as $categoryName ) {
                // For Watermark, clean '.' off of category names to avoid duplicates.
                if ( substr($categoryName, -1) == '.' )
                    $categoryName = substr($categoryName, 0, -1);

                // We know we're going to save a category if we're here, so let's set that up so that we can load it based on a possible match.
                $categoryToSave = new CategoryModel();
                $categoryToSave->groupId = 3;
                $categoryToSave->enabled = true;

                // Let's see if this category exists or not...
                $categories = craft()->elements->getCriteria(ElementType::Category);
                $categories->groupId = 3;
                $categories->title = $categoryName;
                $categories->limit = 1;
                $category = $categories->find();

                // Did we find a match?
                if (count($category) == 1)
                {
                    foreach ($category as $existingCategory)
                    {
                        // Don't create the category
                        $categoryToSave = $existingCategory;
                        $saveCategory = true;
                    }
                }
                else
                {
                    $categoryToSave->getContent()->title = $importEntry->category;
                    craft()->categories->saveCategory($categoryToSave);
                    $saveCategory = true;
                }

                array_push($categoriesToSave, $categoryToSave->id);

            }

            if (!$save)
                continue;

            if ($debug)
            {
                echo $checkImport;
                echo "<br />~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br />~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br /><br />";
            }

            if (!$save)
                continue;

            $entry = craft()->elements->getCriteria(ElementType::Entry);
            $entry->section = 'watermarkBlog';
            $entry->limit = 1;
            $entry->slug = $slug;

            $existingBlogEntry = $entry->find();

            $entryToSave = new EntryModel();

            if (count($existingBlogEntry == 1))
            {
                foreach ($existingBlogEntry as $existingEntry)
                {
                    //echo 'Matched entry: ' . $entry->title . '<br />';
                    $entryToSave = $existingEntry;
                }
            }

            $entryToSave->sectionId = $sectionId;
            $entryToSave->typeId = $typeId;
            $entryToSave->authorId = 1; // Confirmed
            $entryToSave->enabled = true; // Confirmed
            $entryToSave->postDate = date('Y-m-d h:m:s', strtotime($importEntry->pubDate)); // Is time off? Thu, 26 Mar 2015 19:25:04 +0000 vs. 3/26/2015 2:03 AM
            $entryToSave->slug = $slug; // Confirmed
            $entryToSave->getContent()->setAttributes(array(
                'title' => $importEntry->title, // Confirmed
                'postSummaryLegacy' => $importEntry->description, // Confirmed
                'postContentLegacy' => $post, // Confirmed, including image paths
                'displayName' => $importEntry->children("dc", true), // Confirmed to load always
                'metaTitleLegacy' => '', // This doesn't match anything in the new RSS, seems redundant
                'yoastDescriptionLegacy' => $yoastDescriptionLegacy, // Confirmed
                'yoastTitleLegacy' => $yoastTitleLegacy, // Confirmed
                'imageLegacy' => $imageLegacy, // Confirmed
                'disqusThreadIdLegacy' => $disqusThreadIdLegacy // Confirmed
            ));
            if ($saveAuthor)
                $entryToSave->getContent()->postAuthor = $authorToSave; // LinkIt fieldtype needs troubleshooting; Working for entries that do match
            if ($saveEnclosure)
            {
                $entryToSave->getContent()->enclosureUrl = $importEntry->enclosure->attributes()->url; // Confirmed - These are still intact
                $entryToSave->getContent()->enclosureLength = $importEntry->enclosure->attributes()->length; // Confirmed - These are still intact
                $entryToSave->getContent()->enclosureType = $importEntry->enclosure->attributes()->type; // Confirmed - These are still intact
            }
            if ($saveCategory)
                $entryToSave->getContent()->postCategories = $categoriesToSave; // Confirmed

            //echo '*****************************<br />';


            // Saving people/relationship: $entry->getContent()->entriesField = $targetEntryIds;
            // http://craftcms.stackexchange.com/questions/1101/how-to-save-a-matrix-content-of-a-new-entry-in-my-plugin

            if ( craft()->entries->saveEntry($entryToSave) )
            {
                $totalPosts++;
                //echo $totalPosts . '. ' . $importEntry->title . '<br />' . date('Y-m-d h:m:s', strtotime($importEntry->pubDate)) . '<br />' . $slug . '<br /><br />';
                $retVal = $totalPosts;
                continue;
            }
            else
            {
                //$retVal = false;
            }

        }
        if ($debug)
            exit;
        return $retVal;
    }
}
