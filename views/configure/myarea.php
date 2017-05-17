<form action="<?= PluginEngine::getLink($plugin, array(), "configure/myarea") ?>"
      method="post"
      data-dialog
      class="default"
      sautocomplete="off">

    <fieldset>
        <legend>
            <?= _("OwnCloud konfigurieren") ?>
        </legend>

        <? if (UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_OAUTH_ACCESS_TOKEN && UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_OAUTH_REFRESH_TOKEN) : ?>
            <?= MessageBox::info(_("OwnCloud-Rechte sind freigegeben")) ?>
        <? elseif(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT) : ?>
            <div style="text-align: center;">
                <?= \Studip\LinkButton::create(_("OwnCloud für Stud.IP freigeben"), PluginEngine::getURL($plugin, array(), "oauth/request_access_token")) ?>
            </div>
        <? endif ?>

        <? if (!Config::get()->OWNCLOUD_ENDPOINT) : ?>
            <label>
                <?= _("Adresse der OwnCloud") ?>
                <input type="text" name="owncloud[endpoint]" value="<?= htmlReady(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT) ?>" placeholder="<?= "z.B. https://myserver.tdl/owncloud" ?>">
            </label>
        <? endif ?>

        <? if (!Config::get()->OWNCLOUD_CLIENT_ID) : ?>
            <label>
                <?= _("App-ID") ?>
                <input type="text" name="owncloud[client_id]" value="<?= htmlReady(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID) ?>">
            </label>
        <? endif ?>

        <? if (!Config::get()->OWNCLOUD_CLIENT_SECRET) : ?>
            <label>
                <?= _("Secret") ?>
                <input type="text" name="owncloud[client_secret]" value="<?= htmlReady(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_SECRET) ?>">
            </label>
        <? endif ?>

        <label>
            <input type="checkbox" name="owncloud[activated]" value="1"<?= UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACTIVATED ? " checked" : "" ?>>
            <?= _("Aktiviert") ?>
        </label>
    </fieldset>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>