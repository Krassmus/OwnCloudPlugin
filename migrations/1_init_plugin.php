<?php

class InitPlugin extends Migration
{
    public function up()
    {
        $configs =     array("OWNCLOUD_ENDPOINT", "OWNCLOUD_CLIENT_ID", "OWNCLOUD_CLIENT_SECRET");
        $userconfigs = array("OWNCLOUD_ENDPOINT_USER", "OWNCLOUD_CLIENT_ID_USER", "OWNCLOUD_CLIENT_SECRET_USER", "OWNCLOUD_ACTIVATED", "OWNCLOUD_ACCESS_TOKEN", "OWNCLOUD_ACCESS_TOKEN_EXPIRES", "OWNCLOUD_REFRESH_TOKEN");
        foreach ($configs as $config) {
            Config::get()->create($config, array(
                'value' => "",
                'type' => "string",
                'range' => "global",
                'section' => "Owncloud"
            ));
        }
        foreach ($userconfigs as $config) {
            Config::get()->create($config, array(
                'value' => "",
                'type' => in_array($config, ["OWNCLOUD_ACTIVATED"]) ? "boolean" : "string",
                'range' => "user",
                'section' => "Owncloud"
            ));
        }
    }

    public function down()
    {
        $configs =     array("OWNCLOUD_ENDPOINT", "OWNCLOUD_CLIENT_ID", "OWNCLOUD_CLIENT_SECRET");
        $userconfigs = array("OWNCLOUD_ENDPOINT_USER", "OWNCLOUD_CLIENT_ID_USER", "OWNCLOUD_CLIENT_SECRET_USER", "OWNCLOUD_ACTIVATED", "OWNCLOUD_ACCESS_TOKEN", "OWNCLOUD_ACCESS_TOKEN_EXPIRES", "OWNCLOUD_REFRESH_TOKEN");
        foreach ($configs as $config) {
            Config::get()->delete($config);
        }
        foreach ($userconfigs as $config) {
            Config::get()->delete($config);
        }
    }
}