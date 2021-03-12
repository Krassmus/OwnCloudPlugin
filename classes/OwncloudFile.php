<?php

class OwncloudFile implements FileType
{

    public $data = [];
    protected $foldertype = null;

    public function __construct($data, $foldertype)
    {
        $this->data = $data;
        $this->foldertype = $foldertype;
    }

    /**
     * Returns the name of the icon shape that shall be used with the FileType implementation.
     *
     * @param string $role role of icon
     * @return Icon icon for the FileType implementation.
     */
    public function getIcon($role)
    {
        $shape = FileManager::getIconNameForMimeType(
            $this->data['mime_type']
        );
        return Icon::create($shape, $role);
    }

    /**
     * Returns the id of the file which is most likely the id of the FileRef object
     * within the FileType object.
     * @return mixed
     */
    public function getId()
    {
        return $this->data['id'];
    }

    /**
     * Filename of the FileType-object.
     * @return mixed
     */
    public function getFilename()
    {
        return $this->data['name'];
    }

    /**
     * The user_id in Stud.IP if the author has Stud.IP account. If it has none, return null.
     * @return mixed|null
     */
    public function getUserId()
    {
        return $this->data['user_id'];
    }

    /**
     * Return the name of the author as a string.
     * @return string|null
     */
    public function getUserName()
    {
        return get_fullname($this->data['user_id']);
    }


    /**
     * @returns The User object representing the author.
     */
    public function getUser()
    {
        return new User($this->data['user_id']);
    }


    /**
     * Returns the size of the file in bytes. If this is null, the file doesn't exist
     * physically - is probably only a weblink or a request for libraries.
     * @return integer|null
     */
    public function getSize()
    {
        return $this->data['size'];
    }

    /**
     * Returns the URL to download the file. May be sendfile.php?... or an external link.
     * @return string|null
     */
    public function getDownloadURL()
    {
        return $this->data['download_url'];
    }

    /**
     * Returns the number of downloads this file already has. Returns null if information is not available.
     * @return integer|null
     */
    public function getDownloads()
    {
        return null;
    }


    /**
     * Returns the (real) file system path for the file.
     * This is only relevant for FileType implementations storing real files
     * on the server disk. Other implementations shall just return
     * an empty string.
     *
     * @returns The file system path for the file or an empty string if the
     *     file doesn't have a path in the file system.
     */
    public function getPath() : string
    {
        return "";
    }

    /**
     * Returns the UNIX-Timestamp of the last change or null if this information is unknown.
     * @return integer|null
     */
    public function getLastChangeDate()
    {
        return $this->data['chdate'];
    }

    /**
     * Returns the UNIX-timestamp of creation of that file
     * @return integer|null
     */
    public function getMakeDate()
    {
        return $this->data['chdate'];
    }

    /**
     * Returns the description of that FileType object.
     * @return string|null
     */
    public function getDescription()
    {
        return $this->data['description'];
    }

    /**
     * Returns the mime-type of that FileType-object.
     * @return string
     */
    public function getMimeType()
    {
        return $this->data['mime_type'];
    }

    /**
     * @return ContentTermsOfUse
     */
    public function getTermsOfUse()
    {
        return ContentTermsOfUse::findDefault();
    }

    /**
     * Returns an instance of ActionMenu.
     * @return ActionMenu|null
     */
    public function getActionmenu()
    {
        $actionMenu = ActionMenu::get();
        $actionMenu->addLink(
            URLHelper::getURL("dispatch.php/file/details/{$this->getId()}", [
                'to_plugin' => "OwnCloudPlugin",
                'from_plugin' => "OwnCloudPlugin",
                'file_navigation' => 1
            ]),
            _('Info'),
            Icon::create('info-circle', Icon::ROLE_CLICKABLE, ['size' => 20]),
            ['data-dialog' => ''],
            'file-display-info'
        );
        if ($this->isEditable($GLOBALS['user']->id)) {
            $actionMenu->addLink(
                URLHelper::getURL('dispatch.php/file/edit/' . $this->getId()),
                _('Datei bearbeiten'),
                Icon::create('edit', Icon::ROLE_CLICKABLE, ['size' => 20]),
                ['data-dialog' => ''],
                'file-edit'
            );
            $actionMenu->addLink(
                URLHelper::getURL('dispatch.php/file/update/' . $this->getId()),
                _('Datei aktualisieren'),
                Icon::create('refresh', Icon::ROLE_CLICKABLE, ['size' => 20]),
                ['data-dialog' => ''],
                'file-update'
            );
        }
        if ($this->isWritable($GLOBALS['user']->id)) {
            $actionMenu->addLink(
                URLHelper::getURL('dispatch.php/file/choose_destination/move/' . $this->getId()),
                _('Datei verschieben'),
                Icon::create('file+move_right', Icon::ROLE_CLICKABLE, ['size' => 20]),
                ['data-dialog' => 'size=auto'],
                'file-move'
            );
        }
        if ($this->isDownloadable($GLOBALS['user']->id) && $GLOBALS['user']->id !== 'nobody') {
            $actionMenu->addLink(
                URLHelper::getURL('dispatch.php/file/choose_destination/copy/' . $this->getId()),
                _('Datei kopieren'),
                Icon::create('file+add', Icon::ROLE_CLICKABLE, ['size' => 20]),
                ['data-dialog' => 'size=auto'],
                'file-copy'
            );
            $actionMenu->addLink(
                $this->getDownloadURL('force_download'),
                _('Link kopieren'),
                Icon::create('group'),
                ['class' => 'copyable-link'],
                'link-to-clipboard'
            );
        }
        if ($this->isWritable($GLOBALS['user']->id)) {
            $actionMenu->addButton(
                'delete',
                _('Datei löschen'),
                Icon::create('trash', Icon::ROLE_CLICKABLE, ['size' => 20]),
                [
                    'formaction'   => URLHelper::getURL("dispatch.php/file/delete/{$this->getId()}", $flat_view ? ['from_flat_view' => 1] : []),
                    'data-confirm' => sprintf(_('Soll die Datei "%s" wirklich gelöscht werden?'), $this->getFilename()),
                ]
            );
        }
        NotificationCenter::postNotification("FileActionMenuWillRender", $actionMenu, $this);
        return $actionMenu;
    }


    /**
     * Returns a list of Stud.IP button objects that represent actions
     * that shall be visible for the file type in the info dialog.
     *
     * @param array $extra_link_params An optional array of URL parameters
     *     that should be added to Button URLs, if reasonable. The parameter
     *     names are the keys of the array while their values are also the
     *     array item values.
     *
     * @returns Interactable[] A list of Stud.IP buttons (LinkButton or Button).
     */
    public function getInfoDialogButtons(array $extra_link_params = []) : array
    {
        return [];
    }


    /**
     * Deletes that file.
     * @return bool : true on success
     */
    public function delete()
    {
        return $this->foldertype->deleteFile($this->getId());
    }

    /**
     * Returns the FolderTyp of the parent folder.
     * @return FolderType
     */
    public function getFolderType()
    {
        return $this->foldertype;
    }

    /**
     * Determines whether the file is visible for a user.
     *
     * @param string $user_id The user for which the visibility of the file
     *     shall be determined.
     *
     * @return boolean True, if the user is permitted to see the file, false otherwise.
     */
    public function isVisible($user_id = null)
    {
        return true;
    }

    /**
     * Determines if a user may download the file.
     * @param string $user_id The user who wishes to download the file.
     * @return boolean True, if the user is permitted to download the file, false otherwise.
     */
    public function isDownloadable($user_id = null)
    {
        return true;
    }

    /**
     * Determines if a user may edit the file.
     * @param string $user_id The user who wishes to edit the file.
     * @return boolean True, if the user is permitted to edit the file, false otherwise.
     */
    public function isEditable($user_id = null)
    {
        return true;
    }

    /**
     * Determines if a user may write to the file.
     * @param string $user_id The user who wishes to write to the file.
     * @return boolean True, if the user is permitted to write to the file, false otherwise.
     */
    public function isWritable($user_id = null)
    {
        return true;
    }

    /**
     * Returns an object of the class StandardFile or a derived class.
     * @return StandardFile
     */
    public function convertToStandardFile()
    {
        $url = Config::get()->OWNCLOUD_ENDPOINT ?: UserConfig::get($GLOBALS['user']->id)->OWNCLOUD_ENDPOINT_USER;
        if ($url[strlen($url) - 1] !== "/") {
            $url .= "/";
        }
        $webdav = $url . "remote.php/webdav/";

        $header = array();
        $header[] = OwnCloudFolder::getAuthHeader();

        $url = $webdav.$this->getFolderType()->getId()."/".$this->getFilename();

        $r = curl_init();
        curl_setopt($r, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($r, CURLOPT_URL, $url);
        curl_setopt($r, CURLOPT_HTTPHEADER, ($header));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($r, CURLOPT_SSL_VERIFYPEER, (bool) Config::get()->OWNCLOUD_SSL_VERIFYPEER);
        curl_setopt($r, CURLOPT_SSL_VERIFYHOST, (bool) Config::get()->OWNCLOUD_SSL_VERIFYPEER);
        if ($GLOBALS['OWNCLOUD_VERBOSE']) {
            curl_setopt($r, CURLOPT_VERBOSE, true);
        }

        $content = curl_exec($r);

        $info = curl_getinfo($r);
        curl_close($r);
        $path = $GLOBALS['TMP_PATH']."/owncloudplugin_".md5(uniqid());
        file_put_contents(
            $path,
            $content
        );
        return StandardFile::create([
            'name'     => $this->getFilename(),
            'type'     => $this->getMimeType(),
            'size'     => $this->getSize(),
            'tmp_name' => $path
        ], "owncloud");
    }

    /**
     * Returns the content for that additional column, if it exists. You can return null a string
     * or a Flexi_Template as the content.
     * @param string $column_index
     * @return null|string|Flexi_Template
     */
    public function getContentForAdditionalColumn($column_index)
    {
        return null;
    }

    /**
     * Returns an integer that marks the value the content of the given column should be
     * ordered by.
     * @param string $column_index
     * @return integer : order value
     */
    public function getAdditionalColumnOrderWeigh($column_index)
    {
        return 0;
    }


    /**
     * Generates a Flexi_Template containing additional information that are
     * displayes in the information dialog of a file.
     *
     * @param bool $include_downloadable_infos Whether to include information
     *     like file previews that can be downloaded (true) or to not
     *     include them (false). Defaults to false.
     *
     * @returns Flexi_Template|null Either a Flexi_Template containing
     *     additional information or null if no such information shall be
     *     displayed in the information dialog.
     */
    public function getInfoTemplate(bool $include_downloadable_infos = false)
    {
        if (!$include_downloadable_infos) {
            return null;
        }
        $mime_type = $this->getMimeType();
        $relevant_mime_type = false;
        if (FileManager::fileIsImage($this) || FileManager::fileIsAudio($this)
            || FileManager::fileIsVideo($this) ||
            in_array($mime_type, ['application/pdf', 'text/plain'])) {
            $relevant_mime_type = true;
        }
        if (!$relevant_mime_type) {
            return null;
        }

        $factory = new Flexi_TemplateFactory(
            $GLOBALS['STUDIP_BASE_PATH'] . '/templates/filesystem/file_types/'
        );
        $template = $factory->open('standard_file_info');
        $template->set_attribute('mime_type', $mime_type);
        $template->set_attribute('file', $this);
        return $template;
    }
}
