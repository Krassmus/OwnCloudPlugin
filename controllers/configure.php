<?php

class ConfigureController extends PluginController
{
    public function myarea_action()
    {
        if (Navigation::hasItem("/profile/files/OwnCloudPlugin")) {
            Navigation::activateItem('/profile/files/OwnCloudPlugin');
        } else {
            Navigation::activateItem('/profile/files');
        }
        PageLayout::setTitle(_("OwnCloud"));
        if (Request::isPost()) {
            $config = UserConfig::get($GLOBALS['user']->id);
            $data = Request::getArray("owncloud");
            foreach ($data as $key => $value) {
                $config->store("OWNCLOUD_".strtoupper($key).($key === "activated" ? "" : "_USER"), $value);
            }
            if (!$data['activated']) {
                $config->store("OWNCLOUD_ACTIVATED", 0);
            }
            PageLayout::postMessage(MessageBox::success(_("Konfiguration gespeichert.")));
        }
    }
}