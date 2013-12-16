<?php

namespace Craft;

class CraftImportService extends BaseApplicationComponent
{
    public function __construct()
    {

    }

    public function loadEntries()
    {
        $retVal = true;

        // Use SimpleXML to fetch an XML export of channel data from an ExpressionEngine site
        $xml = simplexml_load_file('http://www.YOURDOMAIN.com/blog/export');

        foreach ($xml->blog[0]->entry as $importEntry) {
            // Validate fetch on screen
            /*echo $importEntry->entry_date . '<br />';
            echo $importEntry->title . '<br />';
            echo $importEntry->slug . '<br />';
            echo $importEntry->post . '<br />';
            echo '<br />';*/

            // Swap Assets URLs in posts
            // http://s3.amazonaws.com/YOURBUCKET/uploads
            $newUrl = addslashes('http://s3.amazonaws.com/YOURBUCKET/uploads');
            // Make sure you reference this variable below!
            $post = $importEntry->post;
            // Make sure you run the string containing a subsequent substring first!
            $post = str_replace('http://www.YOURDOMAIN.com/images/uploads', $newUrl, $post);
            $post = str_replace('/images/uploads', $newUrl, $post);

            // Set up new entry
            // Note that we are doing NO validation to detect whether entries already exist
            $entry = new EntryModel(); // Find these in craft/app/models/EntryModel
            $entry->sectionId = 3; // Visit settings for your Section and check the URL
            $entry->typeId = 3; // Visit Entry Types for your Section and check the URL for the Entry Type
            $entry->authorId = 1; // 1 for Admin
            $entry->enabled = true;
            $entry->postDate = $importEntry->entry_date;
            $entry->getContent()->setAttributes(array(
                'title' => $importEntry->title,
                'slug' => $importEntry->slug,
                'post' => $post
            ));
            if ( craft()->entries->saveEntry($entry) )
            {
                // Note that we're doing nothing to limit the number of records processed
                continue;
            } else {
                $retVal = false;
                break;
            }
        }
        return $retVal;
    }
}