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
        PageLayout::setTitle(Config::get()->OWNCLOUD_NAME);
        if (Request::isPost() && Request::submitted("remove")) {
            $config = UserConfig::get($GLOBALS['user']->id);
            $config->unsetValue("OWNCLOUD_ACCESS_TOKEN");
            $config->unsetValue("OWNCLOUD_REFRESH_TOKEN");
            $config->unsetValue("OWNCLOUD_ACTIVATED");
            $config->unsetValue("OWNCLOUD_ACCESS_TOKEN_EXPIRES");
            PageLayout::postMessage(MessageBox::success(_("Zugang zur Owncloud wurde aufgehoben.")));
            $this->redirect(URLHelper::getURL("dispatch.php/files"));
            return;
        }
        if (Request::isPost()) {
            $config = UserConfig::get($GLOBALS['user']->id);
            $data = Request::getArray("owncloud");
            foreach ($data as $key => $value) {
                $config->store("OWNCLOUD_" . strtoupper($key) . ($key === "activated" ? "" : "_USER"), $value);
            }
            if (!$data['activated']) {
                $config->store("OWNCLOUD_ACTIVATED", 0);
                $this->redirect(URLHelper::getURL("dispatch.php/files"));
            } else {
                if (\OwnCloud\OAuth::hasAccessToken()) {
                    $this->redirect(URLHelper::getURL("dispatch.php/files/system/" . $this->plugin->getPluginId()));
                } else {
                    $this->redirect("oauth/request_access_token");
                }
            }
            PageLayout::postMessage(MessageBox::success(_("Konfiguration gespeichert.")));
            return;
        }
    }
}