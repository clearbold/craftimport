<?php

namespace Craft;

class SeedTestPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('COS :: Seed Test');
    }

    public function getVersion()
    {
        return '1.0';
    }

    public function getDeveloper()
    {
        return 'Roi Kingon';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.activeingredients.com';
    }

    public function hasCpSection()
    {
        return true;
    }
}
