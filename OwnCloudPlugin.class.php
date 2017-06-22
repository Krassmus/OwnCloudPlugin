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
        $webdav = $url . "remote.php/webdav";

        $client = new \Sabre\DAV\Client(array(
            'baseUri' => $webdav
        ));
        //$client->addCurlSetting();
        $client->on("beforeRequest", function ($request) {
            var_dump($request);
        });

        $response = $client->request('GET', "", null, array(
            "Authorization: Bearer ".\Owncloud\OAuth::getAccessToken()
        ));

        var_dump($response);die();


        if ($parts['query']) {
            $url .= "?".$parts['query'];
        }
        $url = URLHelper::getURL($url, array(
            'format' => "json",
            'response_code' => "code",
            'client_id' => "",
            'redirect_uri' => ""
        ), true);



        $folder = new VirtualFolderType(array(
            'id' => "OwnCloudTopFolder"
        ), $this->getPluginId());

        $subfolder = new VirtualFolderType(array(
            'id' => "folder1",
            'name' => "Neuer Ordner",
            'parent_id' => "OwnCloudTopFolder"
        ), $this->getPluginId());
        $subfolder->createFile(array(
            'id' => md5(uniqid()),
            'name' => "Statistik.xls",
            'size' => "2102552",
            'mime_type' => "application/xls",
            'description' => "",
            'chdate' => 1471104608,
            'url' => null
            //$item['accessInfo']['epub']['downloadLink']
            //$item['accessInfo']['webReaderLink']
        ));
        if ($folder_id === "folder1") {
            return $subfolder;
        }

        $folder->createSubfolder($subfolder);

        $folder->createFile(array(
            'id' => md5(uniqid()),
            'name' => "TestDatei 1.pdf",
            'size' => "102552",
            'mime_type' => "application/pdf",
            'description' => "",
            'chdate' => 1477904608,
            'url' => null
            //$item['accessInfo']['epub']['downloadLink']
            //$item['accessInfo']['webReaderLink']
        ));

        $folder->createFile(array(
            'id' => md5(uniqid()),
            'name' => "TestDatei 2.html",
            'description' => "Zweite Testdatei",
            'mime_type' => "text/html",
            'size' => "152552",
            'url' => "http://tacspac.com",
            'chdate' => 1477914608,
            //$item['accessInfo']['epub']['downloadLink']
            //$item['accessInfo']['webReaderLink']
        ));

        return $folder;
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