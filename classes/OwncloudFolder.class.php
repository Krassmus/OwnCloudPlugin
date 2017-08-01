<?php

class OwncloudFolder extends VirtualFolderType {

    protected $did_propfind = false;

    public function isWritable($user_id)
    {
        return true;
    }

    public function isEditable($user_id)
    {
        return false;
    }

    public function isSubfolderAllowed($user_id)
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

    /*public function createFile($filedata)
    {
        $filedata['name'];
        $filedata['tmp_path'];
    }*/

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
                    'download_url' => PluginEngine::getURL($this, array(), "download/".($this->id ? $this->id."/" : "").$file_attributes['name'])
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

}