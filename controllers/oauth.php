<?php

class OauthController extends PluginController
{
    public function request_access_token_action()
    {
        URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        //Muss den Nutzer weiterleiten auf den Server, wo der Nutzer die App freischaltet
        $url = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT;
        $url .= "/index.php/apps/oauth2/authorize?response_type=code&client_id=";
        $url .= urlencode(Config::get()->OWNCLOUD_CLIENT_ID ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID);
        $url .= "&redirect_uri=".urlencode(PluginEngine::getURL($this->plugin, array(), "oauth/receive_access_token_action"));


        header("Location: ".$url);
        $this->render_nothing();
    }

    public function receive_access_token_action()
    {
        //Save the access token and refresh-token
        Request::get("code");
    }
}