<?php

class OwncloudFolder extends VirtualFolderType {

    protected $did_propfind = false;

    public static function availableInRange($range_id_or_object, $user_id)
    {
        return $range_id_or_object == 'OwnCloudPlugin';
    }

    public function isWritable($user_id)
    {
        return true;
    }

    public function isEditable($user_id)
    {
        return true;
    }

    public function isSubfolderAllowed($user_id)
    {
        return true;
    }

    public function isFileDownloadable($file_id, $user_id)
    {
        return true;
    }

    public function isFileEditable($fileref_or_id, $user_id)
    {
        return true;
    }

    public function isFileWritable($fileref_or_id, $user_id)
    {
        return true;
    }


    public function store()
    {        
        $old_id = $this->parent_id . '/' . $this->name;

        if ($this->getId() != $old_id) {

            $webdav = $this->getWebDavURL();
            $header = array();
            $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();
            $header[] = "Destination: ". $webdav . $this->id;
    
            $r = curl_init();
            curl_setopt($r, CURLOPT_CUSTOMREQUEST, "MOVE");
            curl_setopt($r, CURLOPT_URL, $webdav . $old_id);
            curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
            curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);    
            curl_exec($r);
            $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
            curl_close($r);

            return ($status >= 200) && ($status < 300);

        }
        return false;
    }

    public function delete()
    {
        return $this->deleteFile($this->id);
    }

    public function deleteFile($file_ref_id)
    {
        $webdav = $this->getWebDavURL();

        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($r, CURLOPT_URL, $webdav . $file_ref_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($r);
        $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);
        return ($status >= 200) && ($status < 300);
    }

    public function createFile($filedata)
    {       
        $webdav = $this->getWebDavURL();

        $file_ref_id = $this->id . (mb_strlen($this->id)?'/':'') .  $filedata['name'];

        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();
       
        $data = $filedata['tmp_name'];
        $fh_res = fopen($data, 'r');

        $r = curl_init();
        curl_setopt($r, CURLOPT_PUT, 1);
        curl_setopt($r, CURLOPT_URL, $webdav . $file_ref_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_INFILE, $fh_res);
        curl_setopt($r, CURLOPT_INFILESIZE, filesize($data));        
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($r);
        $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);
        fclose($fh_res);

        $plugin = PluginManager::getInstance()->getPluginById($this->plugin_id);
        return $plugin->getPreparedFile($file_ref_id);
    }

    public function copyFile($file_ref_id)
    {
        $webdav = $this->getWebDavURL();
        
        $tmp_parts = explode('/', $file_ref_id);
        $destination = $webdav . $this->id . (mb_strlen($this->id)?'/':'') . end($tmp_parts);

        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();
        $header[] = "Destination: ".$destination;

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "COPY");
        curl_setopt($r, CURLOPT_URL, $webdav . $file_ref_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);  

        curl_exec($r);
        $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);
        
        $plugin = PluginManager::getInstance()->getPluginById($this->plugin_id);
        return $plugin->getPreparedFile($file_ref_id);
    }

    public function moveFile($file_ref_id)
    {
        $webdav = $this->getWebDavURL();

        $tmp_parts = explode('/', $file_ref_id);
        $destination = $webdav . $this->id . (mb_strlen($this->id)?'/':'') . end($tmp_parts);

        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();
        $header[] = "Destination: ".$destination;

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "MOVE");
        curl_setopt($r, CURLOPT_URL, $webdav . $file_ref_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($r);
        $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);
        
        $plugin = PluginManager::getInstance()->getPluginById($this->plugin_id);
        return $plugin->getPreparedFile($file_ref_id);
    }

    public function editFile($file_ref_id, $name = null, $description = null,  $content_terms_of_use_id = null)
    {
        if(!$name) {
            return false;
        }      
        
        $webdav = $this->getWebDavURL();        
        $destination = $webdav . $this->id . (mb_strlen($this->id)?'/':'') . $name;

        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();
        $header[] = "Destination: ".$destination;

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "MOVE");
        curl_setopt($r, CURLOPT_URL, $webdav . $file_ref_id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($r);
        $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);
        
        $plugin = PluginManager::getInstance()->getPluginById($this->plugin_id);
        return $plugin->getPreparedFile($file_ref_id);
    }

    public function createSubfolder(FolderType $foldertype)
    {
        $webdav = $this->getWebDavURL();
        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();

        $r = curl_init();

        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "MKCOL");
        curl_setopt($r, CURLOPT_URL, $webdav . $foldertype->getId());
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($r);
        $status = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);

        return (($status >= 200) && ($status < 300)) ? $foldertype : false;
    }

    protected function getWebDavURL()
    {
        $parts = parse_url(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT);
        $url = $parts['scheme']
            ."://"
            .$parts['host']
            .($parts['port'] ? ":".$parts['port'] : "")
            .($parts['path'] ?: "");
        if ($url[strlen($url) - 1] !== "/") {
            $url .= "/";
        }
        $webdav = $url . "remote.php/webdav/";
        return $webdav;
    }

    protected function fetchObjects()
    {
        if ($this->did_propfind === true) {
            return;
        }
        $webdav = $this->getWebDavURL();
        $root = "remote.php/webdav/".$this->id;


        $header = array();
        $header[] = "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken();

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "PROPFIND");
        curl_setopt($r, CURLOPT_URL, $webdav."/".$this->id);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        $xml = curl_exec($r);
        curl_close($r);

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        foreach ($doc->getElementsByTagNameNS("DAV:","response") as $file) {
            //response
            //  -> href
            //  -> propstat
            //    -> prop
            //      -> resourcetype
            //      -> getcontentlength
            //      -> getcontenttype
            //      -> getlastmodified
            //    -> status
            $file_attributes = array();

            foreach ($file->childNodes as $node) {
                if ($node->tagName === "d:href") {
                    $file_attributes['name'] = substr($node->nodeValue, strpos($node->nodeValue, $root) + strlen($root));
                    $file_attributes['name'] = urldecode(array_pop(preg_split("/\//", $file_attributes['name'], 0, PREG_SPLIT_NO_EMPTY)));
                    if (!$file_attributes['name']) {
                        continue 2;
                    }
                }
                if ($node->tagName === "d:propstat") {
                    foreach ($node->childNodes as $prop) {
                        foreach ($prop->childNodes as $attr) {
                            if ($attr->tagName === "d:resourcetype") {
                                $file_attributes['type'] = $attr->childNodes[0] && $attr->childNodes[0]->tagName === "d:collection" ? "folder" : "file";
                            }
                            if ($attr->tagName === "d:getcontentlength") {
                                $file_attributes['size'] = $attr->nodeValue;
                            }
                            if ($attr->tagName === "d:getcontenttype") {
                                $file_attributes['contenttype'] = $attr->nodeValue;
                            }
                            if ($attr->tagName === "d:getlastmodified") {
                                $file_attributes['chdate'] = strtotime($attr->nodeValue);
                            }
                        }
                    }
                }
            }
            if ($file_attributes['type'] === "folder") {
                $this->subfolders[] = new OwncloudFolder(array(
                    'id' => ($this->id ? $this->id."/" : "").$file_attributes['name'],
                    'name' => $file_attributes['name'],
                    'parent_id' => $this->id,
                    'range_type' => $this->plugin_id
                ), $this->plugin_id);
            } else {
                $this->files[] = (object) array(
                    'id' => ($this->id ? $this->id."/" : "").$file_attributes['name'],
                    'name' => $file_attributes['name'],
                    'size' => $file_attributes['size'],
                    'mime_type' => $file_attributes['contenttype'],
                    'description' => "",
                    'chdate' => $file_attributes['chdate'],
                    'download_url' => URLHelper::getURL( "plugins.php/owncloudplugin/download/".($this->id ? $this->id."/" : "").$file_attributes['name'])
                );
            }
        }
        $this->did_propfind = true;
    }

    public function getFiles()
    {
        $this->fetchObjects();
        return $this->files;
    }

    public function getSubfolders()
    {
        $this->fetchObjects();
        return $this->subfolders;
    }

    public function setDataFromEditTemplate($request)
    {

        if (!$request['name']) {
            return MessageBox::error(_('Die Bezeichnung des Ordners fehlt.'));
        }

        $plugin = PluginEngine::getPlugin($request["from_plugin"]);
    
        if (empty($request['parent_id'])) {
            $this->folderdata['id'] = $request['name'];
        } else {
            $this->folderdata['id'] = $request['parent_id'] . '/' . $request['name'];
        }        
        $this->folderdata['parent_id'] = $request['parent_id'];
        $this->folderdata['range_type'] = $plugin->getPluginId();
        $this->folderdata['range_id'] = $plugin->getPluginName();
        $this->folderdata['plugin_id'] = $plugin->getPluginId();
        return $this;
    }

    public function getParent()
    {
        if ($this->id == $this->parent_id) {
            return null;
        }
        $plugin = PluginEngine::getPlugin('OwnCloudPlugin');
        return $plugin->getFolder($this->parent_id);
    }

}
