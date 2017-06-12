<?php

function include_all($path, $regexp = "/\.php$/i")
{
    foreach (scandir($path) as $file) {
        if ($file[0] !== ".") {
            if (is_dir($path."/".$file)) {
                include_all($path."/".$file, $regexp);
            } elseif (preg_match($regexp, $file)) {
                include_once $path."/".$file;
            }
        }
    }
}

include_once __DIR__."/../vendor/guzzle/autoloader.php";

include_all(__DIR__."/../vendor/oauth2-client/src", "/Trait\.php$/i");
include_all(__DIR__."/../vendor/oauth2-client/src", "/Interface\.php$/i");
include_all(__DIR__."/../vendor/oauth2-client/src");

class OauthController extends PluginController
{
    public function request_access_token_action()
    {
        URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        //Muss den Nutzer weiterleiten auf den Server, wo der Nutzer die App freischaltet
        $owncloud = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT;
        if ($owncloud[strlen($owncloud) - 1] !== "/") {
            $owncloud .= "/";
        }
        $url = $owncloud."index.php/apps/oauth2/authorize";

        $url .= "?response_type=code&client_id=";
        $url .= urlencode(Config::get()->OWNCLOUD_CLIENT_ID ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID);
        $url .= "&redirect_uri=".urlencode(PluginEngine::getURL($this->plugin, array(), "oauth/receive_access_token_action", true));

        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => Config::get()->OWNCLOUD_CLIENT_ID ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID,    // The client ID assigned to you by the provider
            'clientSecret'            => Config::get()->OWNCLOUD_CLIENT_SECRET ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_SECRET,   // The client password assigned to you by the provider
            'redirectUri'             => PluginEngine::getURL($this->plugin, array(), "oauth/receive_access_token", true),
            'urlAuthorize'            => $owncloud."index.php/apps/oauth2/authorize",
            'urlAccessToken'          => $owncloud."index.php/apps/oauth2/api/v1/token",
            'urlResourceOwnerDetails' => $owncloud."index.php/apps/oauth2/resource"
        ]);
        $authorizationUrl = $provider->getAuthorizationUrl();

        $_SESSION['oauth2state'] = $provider->getState();

        //die($authorizationUrl);

        header("Location: ".$authorizationUrl);
        $this->render_nothing();
    }

    public function receive_access_token_action()
    {
        //Save the access token and refresh-token
        Request::get("code");

        $owncloud = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT;
        if ($owncloud[strlen($owncloud) - 1] !== "/") {
            $owncloud .= "/";
        }

        /*$provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => Config::get()->OWNCLOUD_CLIENT_ID ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID,    // The client ID assigned to you by the provider
            'clientSecret'            => Config::get()->OWNCLOUD_CLIENT_SECRET ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_SECRET,   // The client password assigned to you by the provider
            'redirectUri'             => PluginEngine::getURL($this->plugin, array(), "oauth/receive_access_token_action", true),
            'urlAuthorize'            => $owncloud."index.php/apps/oauth2/authorize",
            'urlAccessToken'          => $owncloud."index.php/apps/oauth2/api/v1/token",
            'urlResourceOwnerDetails' => $owncloud."index.php/apps/oauth2/resource"
        ]);

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => Request::get("code")
        ]);



        echo 'Access Token: ' . $accessToken->getToken() . "<br>";
        echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
        echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
        echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";

        $config = UserConfig::get($GLOBALS['user']->id);
        $config->store("OWNCLOUD_ACCESS_TOKEN", $accessToken->getToken());
        $config->store("OWNCLOUD_ACCESS_TOKEN_EXPIRES", $accessToken->getExpires());
        $config->store("OWNCLOUD_REFRESH_TOKEN", $accessToken->getRefreshToken());*/





        $header = array();

        $header[] = "Accept: application/json";

        $payload = array(
            'grant_type' => "authorization_code",
            'code' => Request::get("code"),
            'redirect_uri' => PluginEngine::getURL($this->plugin, array(), "oauth/receive_access_token", true),
            'client_id' => \Config::get()->OWNCLOUD_CLIENT_ID ?: \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID,    // The client ID assigned to you by the provider
            'client_secret' => \Config::get()->OWNCLOUD_CLIENT_SECRET ?: \UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_SECRET,   // The client password assigned to you by the provider
            'format' => "json"
        );

        $r = curl_init();
        curl_setopt($r, CURLOPT_URL, $owncloud."index.php/apps/oauth2/authorize");
        curl_setopt($r, CURLOPT_POST, 1);
        curl_setopt($r, CURLOPT_HTTPHEADER, studip_utf8encode($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($r, CURLOPT_POSTFIELDS, studip_utf8encode($payload));

        $result = curl_exec($r);
        $curl_info = curl_getinfo($r);
        curl_close($r);

        $header_size = $curl_info['header_size'];
        $header = substr($result, 0, $header_size);
        $body = studip_utf8decode(json_decode(substr($result, $header_size), true));
        var_dump(($result));

        $config = \UserConfig::get($GLOBALS['user']->id);
        $config->store("OWNCLOUD_ACCESS_TOKEN", $body['access_token']);
        $config->store("OWNCLOUD_ACCESS_TOKEN_EXPIRES", time() + $body['expires_in']);

        $this->render_nothing();
    }
}