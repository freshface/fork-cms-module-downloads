<?php

namespace Backend\Modules\Downloads\Engine;

use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Language;
use Backend\Modules\Downloads\Engine\Images as BackendDownloadsImagesModel;

/**
 * In this file we store all generic functions that we will be using in the Downloads module
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Entries
{
    const QRY_DATAGRID_BROWSE =
        'SELECT i.id, CONCAT(i.first_name, " ", i.last_name) as name, i.email
         FROM downloads_entries AS i
         WHERE i.download_id = ? ORDER BY created_on DESC';

    public static function getAll($id)
    {
        $db = BackendModel::get('database');

        $fileUrl = SITE_URL . FRONTEND_FILES_URL . '/Downloads/File/';

        $return =  (array) $db->getRecords(
             'SELECT i.first_name,  i.last_name, i.email, i.file
              FROM downloads_entries AS i
              WHERE i.download_id = ?',
             array((int) $id)
         );

        foreach ($return as &$record) {
            if (!empty($record['file'])) {
                $record['file'] = $fileUrl . $record['file'];
            }
        }

        return  $return;
    }
}
