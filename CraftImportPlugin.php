<?php

namespace Craft;

class CraftImportPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Craft Simple XML Import');
    }

    public function getVersion()
    {
        return '0.2.01';
    }

    public function getDeveloper()
    {
        return 'Mark Reeves';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.clearbold.com';
    }

    public function hasCpSection()
    {
        return true;
    }
}
