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
class Model
{
    const QRY_DATAGRID_BROWSE =
        'SELECT i.id, c.name,  i.sequence, i.hidden
         FROM downloads AS i
         INNER JOIN download_content as c  on i.id = c.download_id
         WHERE c.language = ? AND i.status = ? ORDER BY sequence DESC';

       /**
       * Get the maximum Team sequence.
       *
       * @return int
       */
      public static function getMaximumSequence()
      {
          return (int) BackendModel::get('database')->getVar(
              'SELECT MAX(i.sequence)
               FROM downloads AS i'
          );
      }

     /**
      * Retrieve the unique URL for an item
      *
      * @param string $URL The URL to base on.
      * @param int    $id  The id of the item to ignore.
      * @return string
      */
     public static function getURL($URL, $language, $id = null)
     {
         $URL = (string) $URL;

         // get db
         $db = BackendModel::getContainer()->get('database');

         // new item
         if ($id === null) {
             // already exists
             if ((bool) $db->getVar(
                 'SELECT 1
                  FROM downloads AS i
                  INNER JOIN download_content AS m ON i.id = m.download_id
                  WHERE m.language = ? AND m.url = ?
                  LIMIT 1',
                 array($language, $URL)
             )
             ) {
                 $URL = BackendModel::addNumber($URL);

                 return self::getURL($URL, $language);
             }
         } else {
             // current category should be excluded
             if ((bool) $db->getVar(
                 'SELECT 1
                  FROM downloads AS i
                  INNER JOIN download_content AS m ON i.id = m.download_id
                  WHERE m.language = ? AND m.url = ? AND i.id != ?
                  LIMIT 1',
                 array($language, $URL, $id)
             )
             ) {
                 $URL = BackendModel::addNumber($URL);

                 return self::getURL($URL,$language, $id);
             }
         }

         return $URL;
     }

    /**
     * Delete a certain item
     *
     * @param int $id
     */
    public static function delete($id)
    {
        BackendModel::get('database')->delete('downloads', 'id = ?', (int) $id);
        BackendModel::get('database')->delete('download_content', 'download_id = ?', (int) $id);
        BackendModel::get('database')->delete('downloads_linked_catgories', 'download_id = ?', (int) $id);

        $images = (array) BackendDownloadsImagesModel::getAll((int) $id);
        foreach($images as $image){
            BackendModel::deleteThumbnails(FRONTEND_FILES_PATH . '/' . BackendModel::get('url')->getModule() . '/uploaded_images',  $image['filename']);
        }

        BackendModel::get('database')->execute('DELETE c FROM download_images_content c INNER JOIN download_images i ON c.image_id = i.id WHERE i.download_id = ?', array((int) $id));
        BackendModel::get('database')->delete('download_images', 'download_id = ?', (int) $id);
    }

    /**
     * Checks if a certain item exists
     *
     * @param int $id
     * @return bool
     */
    public static function exists($id)
    {
        return (bool) BackendModel::get('database')->getVar(
            'SELECT 1
             FROM downloads AS i
             WHERE i.id = ?
             LIMIT 1',
            array((int) $id)
        );
    }

    /**
     * Fetches a certain item
     *
     * @param int $id
     * @return array
     */
    public static function get($id)
    {
        $db = BackendModel::get('database');

        $return =  (array) $db->getRecord(
            'SELECT i.*, UNIX_TIMESTAMP(i.publish_on) as publish_on
             FROM downloads AS i
             WHERE i.id = ?',
            array((int) $id)
        );

        // data found
        $return['content'] = (array) $db->getRecords(
            'SELECT i.* FROM download_content AS i
            WHERE i.download_id = ?',
            array((int) $id), 'language');

        return  $return;

    }





    /**
     * Insert an item in the database
     *
     * @param array $item
     * @return int
     */
    public static function insert(array $item)
    {
        $item['created_on'] = BackendModel::getUTCDate();
        $item['edited_on'] = BackendModel::getUTCDate();

        return (int) BackendModel::get('database')->insert('downloads', $item);
    }

    public static function insertContent(array $content, $parentData)
    {
        foreach($content as &$item){

            if($parentData['download_widget'] == 'Y')
            {
                $data = [
                    'id' => $item['download_id'],
                    'language' => $item['language'],
                    'extra_label' => 'Download: ' . $item['name'],
                    'edit_url' => BackendModel::createURLForAction('Edit') . '&id=' . $item['download_id'],
                ];

                $item['download_widget_extra_id'] = BackendModel::insertExtra(
                    'widget',
                    'Downloads',
                    'Download',
                    'Download',
                    $data
                );
            }

            BackendModel::get('database')->insert('download_content', $item);
        }
    }

    /**
     * Updates an item
     *
     * @param array $item
     */
    public static function update(array $item)
    {
        $item['edited_on'] = BackendModel::getUTCDate();

        BackendModel::get('database')->update(
            'downloads', $item, 'id = ?', (int) $item['id']
        );
    }

    public static function updateContent(array $content, $id, $parentData)
    {
        $db = BackendModel::get('database');
        foreach($content as $language => $row)
        {


            if($parentData['download_widget'] == 'Y' && $row['download_widget_extra_id'])
            {
                // update
                $data = [
                    'id' => $row['download_id'],
                    'language' => $row['language'],
                    'extra_label' => 'Download: ' . $row['name'],
                    'edit_url' => BackendModel::createURLForAction('Edit') . '&id=' . $row['download_id'],
                ];

                BackendModel::updateExtra($row['download_widget_extra_id'], 'data', $data);

            } else if($parentData['download_widget'] == 'Y' && !$row['download_widget_extra_id'])
            {
                // insert
                $data = [
                    'id' => $row['download_id'],
                    'language' => $row['language'],
                    'extra_label' => 'Slideshow: ' . $row['name'],
                    'edit_url' => BackendModel::createURLForAction('Edit') . '&id=' . $row['download_id'],
                ];

                $row['download_widget_extra_id'] = BackendModel::insertExtra(
                    'widget',
                    'Downloads',
                    'Download',
                    'Download',
                    $data
                );

            } else if($parentData['download_widget'] == 'N' && $row['download_widget_extra_id'])
            {
                // remove

                BackendModel::deleteExtraById($row['download_widget_extra_id'] , true);
                $row['download_widget_extra_id'] = null;
            }

            $db->update('download_content', $row, 'download_id = ? AND language = ?', array($id, $language));
        }
    }
}
