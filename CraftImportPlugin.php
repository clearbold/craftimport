<?php

namespace Craft;

class CraftImportPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Craft Import');
    }

    public function getVersion()
    {
        return '0.1';
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

    /*public function getSettingsHtml()
    {
        return craft()->templates->render(
            'craftimport/index'
        );
    }*/
}
