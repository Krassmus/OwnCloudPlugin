<?php

require_once __DIR__."/classes/OAuth.class.php";
require_once __DIR__."/classes/OwncloudFolder.class.php";

class OwnCloudPlugin extends StudIPPlugin implements FilesystemPlugin {

    public function getFileSelectNavigation()
    {
        $nav = new Navigation(_("OwnCloud"));
        $nav->setImage(Icon::create("cloud", "clickable"));
        return $nav;
    }

    public function getFolder($folder_id = null)
    {
        if ($folder_id[0] === "/") {
            $folder_id = substr($folder_id, 1);
        }
        if ($folder_id && !$this->isFolder($folder_id)) {
            return null;
        }

        $folder_path = explode("/", $folder_id);
        $name = rawurldecode(array_pop($folder_path));
        $parent_folder_id = implode("/", $folder_path);
        $folder = new OwncloudFolder(array(
            'id' => $folder_id,
            'name' => $name,
            'parent_id' => $parent_folder_id,
            'range_type' => $this->getPluginId(),
            'range_id' => $this->getPluginName()
        ), $this->getPluginId());
        return $folder;
    }

    public function download_action()
    {
        $args = func_get_args();
        $file_id = implode("/", array_map("rawurlencode", $args));

        $url = UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT ?: Config::get()->OWNCLOUD_ENDPOINT_USER;
        if ($url[strlen($url) - 1] !== "/") {
            $url .= "/";
        }
        $webdav = $url . "remote.php/webdav/";


        $header = array();
        $header[] = OwnCloudFolder::getAuthHeader();

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($r, CURLOPT_URL, $webdav . $file_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($r, CURLOPT_SSL_VERIFYPEER, (bool) Config::get()->OWNCLOUD_SSL_VERIFYPEER);

        $content = curl_exec($r);
        $info = curl_getinfo($r);
        curl_close($r);

        header("Content-Length: ".$info['size_download']);
        header("Content-Type: ".$info['content_type']);
        echo $content;
        return;
    }

    /**
     * @param $file_id : this is a path to the file. Remind that the chunks of this path need to be rawurlencoded!
     * @param bool $with_blob : should OwnCloudPlugin retrieve the blib of the file as well?
     * @return FileRef|null : Returns the FileRef on success and null if the file doesn't exist.
     */
    public function getPreparedFile($file_id, $with_blob = false)
    {
        if (!$this->isFile($file_id)) {
            return null;
        }
        
        $folder_path = explode("/", $file_id);
        $filename = rawurldecode(array_pop($folder_path));
        $folder_id = implode("/", $folder_path);
        $name = array_pop($folder_path);
        $parent_folder_id = implode("/", $folder_path);

        $folder = new OwncloudFolder(array(
            'id' => $folder_id,
            'name' => $name,
            'parent_id' => $parent_folder_id,
            'range_type' => $this->getPluginId(),
            'range_id' => $this->getPluginName()
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
        $file->mime_type    = $info->mime_type;
        $file->download_url = $info->download_url;
        $file->mkdate       = $info->chdate;
        $file->chdate       = $info->chdate;
        $file->content_terms_of_use_id = 'UNDEF_LICENSE';
        
        if ($with_blob) {
            $url = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT_USER;
            if ($url[strlen($url) - 1] !== "/") {
                $url .= "/";
            }
            $webdav = $url . "remote.php/webdav/";


            $header = array();
            $header[] = OwnCloudFolder::getAuthHeader();

            $r = curl_init();
            curl_setopt($r, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($r, CURLOPT_URL, $webdav.$file_id);
            curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
            curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($r, CURLOPT_SSL_VERIFYPEER, (bool) Config::get()->OWNCLOUD_SSL_VERIFYPEER);

            $content = curl_exec($r);
            $info = curl_getinfo($r);
            curl_close($r);
            $path = $GLOBALS['TMP_PATH']."/".md5(uniqid());
            file_put_contents(
                $path,
                $content
            );
            $file->path_to_blob = $path;
        }

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

    protected function getType($id)
    {
        $url = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT_USER;
        if ($url[strlen($url) - 1] !== "/") {
            $url .= "/";
        }
        $webdav = $url . "remote.php/webdav/";
        $header = array();
        $header[] = OwnCloudFolder::getAuthHeader();
        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "PROPFIND");
        curl_setopt($r, CURLOPT_URL, $webdav."/".$id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($r, CURLOPT_SSL_VERIFYPEER, (bool) Config::get()->OWNCLOUD_SSL_VERIFYPEER);
        $xml = curl_exec($r);
        curl_close($r);
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        foreach ($doc->getElementsByTagNameNS("DAV:","response") as $file) {
            foreach ($file->childNodes as $node) {
                if (strtolower($node->tagName) === "d:propstat") {
                    if ($node->childNodes) {
                        foreach ($node->childNodes as $prop) {
                            if ($prop->childNodes) {
                                foreach ($prop->childNodes as $attr) {
                                    if (strtolower($attr->tagName) === "d:resourcetype") {
                                        return $file_attributes['type'] = ($attr->childNodes[0] && strtolower($attr->childNodes[0]->tagName) === "d:collection")
                                            ? "folder"
                                            : "file";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $file_attributes['type'];
    }

    protected function isFolder($id)
    {
       return $this->getType($id) == 'folder';
    }

    protected function isFile($id)
    {
        return $this->getType($id) == 'file';
    }
}
