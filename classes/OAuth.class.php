<?php

namespace Owncloud;

class OAuth {

    static public function request($url, $type = "get", $payload = array())
    {
        $owncloud = \Config::get()->OWNCLOUD_ENDPOINT ?: \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT;
        if ($owncloud[strlen($owncloud) - 1] !== "/") {
            $owncloud .= "/";
        }

        $header = array();

        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            throw new Exception("No valid access token. Please refresh you connection to Owncloud.");
        }

        $header[] = "Authorization: Bearer ".$accessToken;

        $r = curl_init();
        curl_setopt($r, CURLOPT_URL, $url);
        curl_setopt($r, CURLOPT_POST, $type === "get" ? 0 : 1);
        curl_setopt($r, CURLOPT_HTTPHEADER, studip_utf8encode($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($r, CURLOPT_POSTFIELDS, studip_utf8encode($payload));

        $result = curl_exec($r);
        curl_close($r);

        $header_size = curl_getinfo($r, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $body = substr($result, $header_size);
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

        $header[] = "Accept: application/json";
        $header[] = "Authorization: Basic ".base64_encode($client_id . ":" .$client_secret);

        $payload = array(
            'grant_type' => "refresh_token",
            'refresh_token' => \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_REFRESH_TOKEN,
            'access_token' => \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACCESS_TOKEN,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'format' => "json"
        );

        $r = curl_init();
        curl_setopt($r, CURLOPT_URL, $owncloud."index.php/apps/oauth2/api/v1/token");
        curl_setopt($r, CURLOPT_POST, 1);
        curl_setopt($r, CURLOPT_HTTPHEADER, studip_utf8encode($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($r, CURLOPT_POSTFIELDS, studip_utf8encode($payload));

        $json = curl_exec($r);
        curl_close($r);

        echo "Refresh-Response: ";
        var_dump($json); die();

        $json = studip_utf8decode(json_decode($json, true));

        if ($json['error']) {
            PageLayout::postError(_("Authentifizierungsfehler:")." ".$json['error']);
        } else {
            $config = \UserConfig::get($GLOBALS['user']->id);
            $config->store("OWNCLOUD_ACCESS_TOKEN", $json['access_token']);
            //$config->store("OWNCLOUD_REFRESH_TOKEN", $json['refresh_token']);
            $config->store("OWNCLOUD_ACCESS_TOKEN_EXPIRES", time() + $json['expires_in']);
        }
    }
}