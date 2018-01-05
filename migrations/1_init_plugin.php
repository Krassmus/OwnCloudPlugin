<?php

class InitPlugin extends Migration {
    public function up() {
        $configs = array("OWNCLOUD_ENDPOINT", "OWNCLOUD_CLIENT_ID", "OWNCLOUD_CLIENT_SECRET", "OWNCLOUD_ACTIVATED", "OWNCLOUD_ACCESS_TOKEN", "OWNCLOUD_ACCESS_TOKEN_EXPIRES", "OWNCLOUD_REFRESH_TOKEN");
        foreach ($configs as $config) {
            Config::get()->create($config, array(
                'value' => "",
                'type' => $config === "OWNCLOUD_ACTIVATED" ? "boolean" : "string",
                'range' => "user",
                'section' => "Owncloud"
            ));
        }
    }
}