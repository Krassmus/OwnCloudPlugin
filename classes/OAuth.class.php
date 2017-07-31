<?php

namespace Owncloud;

class OAuth {

    static public function request($url, $type = "get", $payload = array())
    {
        $header = array();

        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            throw new Exception("No valid access token. Please refresh you connection to Owncloud.");
        }

        $header[] = "Authorization: Bearer ".$accessToken;

        $r = curl_init();
        curl_setopt($r, CURLOPT_URL, $url);
        curl_setopt($r, CURLOPT_POST, $type === "get" ? 0 : 1);
        curl_setopt($r, CURLOPT_HTTPHEADER, $header);
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($r, CURLOPT_POSTFIELDS, $payload);

        $result = curl_exec($r);
        curl_close($r);

        return $result;
    }

    static public function isReady()
    {
        return self::hasAccessToken() || \Config::get()->OWNCLOUD_REFRESH_TOKEN;
    }

    static public function getAccessToken()
    {
        if (self::hasAccessToken()) {
            return \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACCESS_TOKEN;
        } elseif(\UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_REFRESH_TOKEN) {
            self::refreshAccessToken();
            return \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACCESS_TOKEN;
        } else {
            return false;
        }
    }

    static public function hasAccessToken()
    {
        return \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACCESS_TOKEN
            && (\UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACCESS_TOKEN_EXPIRES >= time());
    }

    static public function refreshAccessToken()
    {
        $owncloud = \Config::get()->OWNCLOUD_ENDPOINT ?: \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT;
        if ($owncloud[strlen($owncloud) - 1] !== "/") {
            $owncloud .= "/";
        }
        $client_id  = \Config::get()->OWNCLOUD_CLIENT_ID ?: \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID; // The client ID assigned to you by the provider
        $client_secret = \Config::get()->OWNCLOUD_CLIENT_SECRET ?: \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_SECRET; // The client password assigned to you by the provider

        $header = array();

        $header[] = "Authorization: Basic ".base64_encode($client_id . ":" .$client_secret);
        $header[] = "Accept: application/json";

        $payload = array(
            'grant_type' => "refresh_token",
            'refresh_token' => \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_REFRESH_TOKEN,
            'code' => \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_REFRESH_TOKEN,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'format' => "json",
            'access_token' => \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACCESS_TOKEN
        );

        $r = curl_init();
        curl_setopt($r, CURLOPT_URL, $owncloud."index.php/apps/oauth2/api/v1/token");
        curl_setopt($r, CURLOPT_POST, 1);
        curl_setopt($r, CURLOPT_HTTPHEADER, $header);
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($r, CURLOPT_POSTFIELDS, $payload);

        $json = curl_exec($r);
        curl_close($r);

        $json = json_decode($json, true);

        if (!$json) {
            URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
            //Muss den Nutzer weiterleiten auf den Server, wo der Nutzer die App freischaltet
            $owncloud = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT;
            if ($owncloud[strlen($owncloud) - 1] !== "/") {
                $owncloud .= "/";
            }
            URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
            $client_id = Config::get()->OWNCLOUD_CLIENT_ID ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID;
            $redirect_uri = PluginEngine::getURL($this->plugin, array(), "oauth/receive_access_token", true);

            $url = $owncloud."index.php/apps/oauth2/authorize";

            $_SESSION['oauth2state'] = md5(uniqid());
            $url .= "?state=".urlencode($_SESSION['oauth2state'])
                . "&response_type=code"
                . "&approval_prompt=auto"
                . "&redirect_uri=".urlencode($redirect_uri)
                . "&client_id=".urlencode($client_id);

            header("Location: ".$url);
            exit;
        } elseif ($json['error']) {
            PageLayout::postError(_("Authentifizierungsfehler:")." ".$json['error']);
        } else {
            PageLayout::postInfo("Acces-Token wurde erfolgreich erneuert.");
            $config = \UserConfig::get($GLOBALS['user']->id);
            $config->store("OWNCLOUD_ACCESS_TOKEN", $json['access_token']);
            //$config->store("OWNCLOUD_REFRESH_TOKEN", $json['refresh_token']);
            $config->store("OWNCLOUD_ACCESS_TOKEN_EXPIRES", time() + $json['expires_in']);
        }
    }
}