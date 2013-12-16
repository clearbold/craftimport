craftimport
============

Craft CMS plugin to import entries from XML

### Credits

Initial starting point courtesy [Roi Kingon](https://plus.google.com/112173526450245116573/posts).

### Install

* Drop the craftimport directory into your craft/plugins directory.
* Navigate to Settings -> Plugins and click "Install" to the right of Craft Import.

This will create a "Craft Import" tab at the top of your admin. It's safe to click that without running an import.

### Import

Click the "Load Entries" button to run your import. **Recommended: Backup your database before running an import.**

### Configuration

All the magic happens in services/CraftImportService.php

* Update the URL on `Line 17` to point to well-formed XML. I created an XML template in an ExpressionEngine site as my source.
* Update `Lines 30-39` to match your Craft configuration and source nodes. Reference inline comments.
* **Note that** the importer does not have a limit on the number of entries and may time out.