<?php

require_once __DIR__."/classes/OAuth.class.php";
require_once __DIR__."/classes/OwncloudFolder.class.php";
include __DIR__.'/vendor/autoload.php';

class OwnCloudPlugin extends StudIPPlugin implements FilesystemPlugin {

    public function getFileSelectNavigation()
    {
        $nav = new Navigation(_("OwnCloud"));
        $nav->setImage(Icon::create("cloud", "clickable"));
        return $nav;
    }

    public function getFolder($folder_id = null)
    {
        $folder_path = explode("/", $folder_id);
        array_pop($folder_path);
        $parent_folder_id = implode("/", $folder_path);
        $folder = new OwncloudFolder(array(
            'id' => $folder_id,
            'parent_id' => $parent_folder_id,
            'range_type' => $this->getPluginId()
        ), $this->getPluginId());
        return $folder;
    }

    public function download_action()
    {
        $args = func_get_args();
        $file_id = implode("/", $args);

        $parts = parse_url(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT);
        $url = $parts['scheme']
            .urlencode(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_USERNAME)
            .":"
            .urlencode(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_PASSWORD)
            ."@"
            .$parts['host']
            .($parts['port'] ? ":".$parts['port'] : "")
            .($parts['path'] ?: "");
        if ($url[strlen($url) - 1] !== "/") {
            $url .= "/";
        }
        $webdav = $url . "remote.php/webdav/";


        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($r, CURLOPT_URL, $webdav."/".$file_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        $content = curl_exec($r);
        $info = curl_getinfo($r);
        curl_close($r);

        header("Content-Length: ".$info['size_download']);
        header("Content-Type: ".$info['content_type']);
        echo $content;
        return;
    }

    public function getPreparedFile($file_id)
    {
        $folder_path = explode("/", $file_id);
        $filename = array_pop($folder_path);
        $folder_id = implode("/", $folder_path);
        array_pop($folder_path);
        $parent_folder_id = implode("/", $folder_path);

        $folder = new OwncloudFolder(array(
            'id' => $folder_id,
            'parent_id' => $parent_folder_id,
            'range_type' => $this->getPluginId()
        ), $this->getPluginId());

        foreach ($folder->getFiles() as $file_info) {
            if ($file_info->name === $filename) {
                $info = $file_info;
                break;
            }
        }

        $file = new FileRef();
        $file->id           = $file_id;
        $file->foldertype   = $folder;
        $file->name         = $filename;
        $file->size         = $info->size;
        $file->mime_type    = $info->download_url;
        $file->download_url = $info->mime_type;
        $file->mkdate       = $info->chdate;
        $file->chdate       = $info->chdate;

        return $file;
    }

    public function filesystemConfigurationURL()
    {
        return PluginEngine::getURL($this, array(), "configure/myarea");
    }

    public function hasSearch()
    {
        return false;
    }

    public function getSearchParameters()
    {
        // TODO: Implement getSearchParameters() method.
    }

    public function search($text, $parameters = array())
    {
        return null;
    }

    public function isSource()
    {
        return UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACTIVATED;
    }

    public function isPersonalFileArea()
    {
        return UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACTIVATED;
    }

}