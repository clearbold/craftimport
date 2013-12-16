craftimport
============

Craft CMS plugin to import entries from XML.

### Credits

Initial starting point courtesy [Roi Kingon](https://plus.google.com/112173526450245116573/posts).

### Install

* Drop the craftimport directory into your craft/plugins directory.
* Navigate to Settings -> Plugins and click "Install" to the right of Craft Import.

This will create a "Craft Import" tab at the top of your admin. It's safe to click that without running an import.

### Import

Click the "Load Entries" button to run your import. **Recommended: Backup your database before running an import.**

### Images & Assets

The previous blog (in my example) stored images locally and went through an upgrade, so Wygwam fields contained "/images/uploads" and "http://www.DOMAIN.com/images/uploads" references.

In Craft, I'm using Amazon S3 for Assets. I added an image to a blog post to test the syntax, and then added `Lines 27-34` to update those.

Be sure to comment out these lines if you don't need them, as they will replace any instances of "/images/uploads".

### Configuration

All the magic happens in services/CraftImportService.php

* Update the URL on `Line 17` to point to well-formed XML. I created an XML template in an ExpressionEngine site as my source.
* Update `Lines 39-48` to match your Craft configuration and source nodes. Reference inline comments.
* **Note that** the importer does not have a limit on the number of entries and may time out.

### Customization

Customize the plugin's landing page by editing templates/index.html. Add a "**do not use!**" warning!