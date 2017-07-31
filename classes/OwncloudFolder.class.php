<?php

class OwncloudFolder extends VirtualFolderType {

    public function isWritable($user_id)
    {
        return false;
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
        return true;
    }

    public function createFile($filedata)
    {
        $filedata['name'];
        $filedata['tmp_path'];
    }

}