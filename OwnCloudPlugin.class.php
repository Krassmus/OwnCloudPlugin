<?php

require_once __DIR__."/classes/OAuth.class.php";
include __DIR__.'/vendor/autoload.php';

class OwnCloudPlugin extends StudIPPlugin implements FilesystemPlugin {

    public function getFileSelectNavigation()
    {
        $nav = new Navigation(_("OwnCloud"));
        $nav->setImage(Icon::create("cloud2", "clickable"));
        return $nav;
    }

    public function getFolder($folder_id = null)
    {
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
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "PROPFIND");
        curl_setopt($r, CURLOPT_URL, $webdav);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);

        $xml = curl_exec($r);
        curl_close($r);

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $folder = new VirtualFolderType(array(
            'id' => "OwnCloudTopFolder"
        ), $this->getPluginId());

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
                    $file_attributes['name'] = substr($node->nodeValue, strpos($node->nodeValue, "remote.php/webdav/"));
                    $file_attributes['name'] = array_pop(preg_split("/\//", $file_attributes['name'], 0, PREG_SPLIT_NO_EMPTY));
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
                $subfolder = new VirtualFolderType(array(
                    'id' => $folder_id."/".$file_attributes['name'],
                    'name' => $file_attributes['name'],
                    'parent_id' => $folder_id
                ), $this->getPluginId());
                $folder->createSubfolder($subfolder);
            } else {
                $folder->createFile(array(
                    'id' => $folder_id."/".$file_attributes['name'],
                    'name' => $file_attributes['name'],
                    'size' => $file_attributes['size'],
                    'mime_type' => $file_attributes['contenttype'],
                    'description' => "",
                    'chdate' => $file_attributes['chdate']
                ));
            }
        }

        return $folder;
        die();



        $client = new \Sabre\DAV\Client(array(
            'baseUri' => $webdav
        ));

        $response = $client->propfind("collection", array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
        ), 1, array(
            "Authorization" => "Bearer ".\Owncloud\OAuth::getAccessToken()
        ));

        /*$response = $client->request('GET', "", null, array(
            "Authorization" => "Bearer ".\Owncloud\OAuth::getAccessToken()
        ));*/

        var_dump($response);die();

    }

    public function getPreparedFile($file_id)
    {
        //TODO

        $url = "https://www.googleapis.com/books/v1/volumes/".$file_id."?key=".urlencode(self::$googlebooksapikey);
        $info = file_get_contents($url);
        $info = studip_utf8decode(json_decode($info, true));
        $download = $info['accessInfo']['pdf']['downloadLink'] ?: $info['accessInfo']['epub']['downloadLink'];
        $tmp_path = $GLOBALS['TMP_PATH']."/".md5(uniqid());
        if (!$download) {
            var_dump($info);
        } else {
            file_put_contents($tmp_path, file_get_contents($download));
            $file = array(
                'name' => $info['volumeInfo']['title'] . ($info['accessInfo']['pdf']['downloadLink'] ? ".pdf" : ".epub"),
                'size' => filesize($tmp_path),
                'type' => $info['accessInfo']['pdf']['downloadLink'] ? "application/pdf" : "application/epub+zip",
                'tmp_path' => $tmp_path,
                'description' => $info['volumeInfo']['publishedDate'].", ".implode(", ", (array) $info['volumeInfo']['authors'])
            );
            return $file;
        }
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