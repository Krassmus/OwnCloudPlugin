<form action="<?= PluginEngine::getLink($plugin, array(), "configure/myarea") ?>"
      method="post"
      data-dialog
      class="default owncloud"
      autocomplete="off">

    <fieldset>
        <legend>
            <?= sprintf(_("%s konfigurieren"), Config::get()->OWNCLOUD_NAME) ?>
        </legend>

        <? if (\Owncloud\OAuth::isReady()) : ?>
            <?= MessageBox::info(sprintf(_("%s ist verknüpft"), Config::get()->OWNCLOUD_NAME)) ?>
        <? elseif((UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT_USER || Config::get()->OWNCLOUD_ENDPOINT) && UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ACTIVATED) : ?>
            <div style="text-align: center;">
                <?= \Studip\LinkButton::create(sprintf(_("%s für Stud.IP freigeben"), Config::get()->OWNCLOUD_NAME), PluginEngine::getURL($plugin, array(), "oauth/request_access_token")) ?>
            </div>
        <? endif ?>

        <? if (!Config::get()->OWNCLOUD_ENDPOINT) : ?>
            <label>
                <?= sprintf(_("Adresse der %s"), Config::get()->OWNCLOUD_NAME) ?>
                <input type="text" name="owncloud[endpoint]" value="<?= htmlReady(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT_USER) ?>" placeholder="<?= "z.B. https://myserver.tdl/owncloud" ?>">
            </label>
        <? endif ?>

        <? if (!Config::get()->OWNCLOUD_CLIENT_ID) : ?>
            <label>
                <?= _("App-ID") ?>
                <input type="text" name="owncloud[client_id]" value="<?= htmlReady(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_ID_USER) ?>">
            </label>
        <? endif ?>

        <? if (!Config::get()->OWNCLOUD_CLIENT_SECRET) : ?>
            <label>
                <?= _("Secret") ?>
                <input type="text" name="owncloud[client_secret]" value="<?= htmlReady(UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_CLIENT_SECRET_USER) ?>">
            </label>

            <label>
                <? URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']) ?>
                <?= sprintf(_("Redirect-URI (zum Eintragen in der %s)"), Config::get()->OWNCLOUD_NAME) ?>
                <input type="text" readonly value="<?= htmlReady(PluginEngine::getURL($plugin, array(), "oauth/receive_access_token"), true) ?>">
                <? URLHelper::setBaseURL("/") ?>
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

<style>
    form.default.owncloud input[readonly] {
        background-color: #e1e3e4;
        background-image: url(<?= Icon::create("lock-locked", "info_alt")->asImagePath() ?>);
        background-repeat: no-repeat;
        background-position: calc(100% - 5px) 4px;
        background-size: 20px 20px;
    }
</style>