<?php

namespace Backend\Modules\Downloads\Engine;

use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Language;

/**
 * In this file we store all generic functions that we will be using in the Downloads module
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Images
{

      const QRY_DATAGRID_BROWSE_IMAGES_FOR_PROJECT  =
        'SELECT i.id, i.filename, i.sequence
        FROM download_images AS i
        WHERE i.download_id = ?
        ORDER BY i.sequence';


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
                     INNER JOIN download_images_content AS m ON i.id = m.image_id
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
                     INNER JOIN download_images_content AS m ON i.id = m.image_id
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
           BackendModel::get('database')->delete('download_images', 'id = ?', (int) $id);
           BackendModel::get('database')->delete('download_images_content', 'image_id = ?', (int) $id);
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
                FROM download_images AS i
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
               'SELECT i.*
                FROM download_images AS i
                WHERE i.id = ?',
               array((int) $id)
           );

           // data found
           $return['content'] = (array) $db->getRecords(
               'SELECT i.* FROM download_images_content AS i
               WHERE i.image_id = ?',
               array((int) $id), 'language');

           return  $return;

        }

        public static function getAll($id)
        {
           $db = BackendModel::get('database');

           $return =  (array) $db->getRecords(
               'SELECT i.*
                FROM download_images AS i
                WHERE i.download_id = ? ORDER BY i.sequence',
               array((int) $id)
           );

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

           return (int) BackendModel::get('database')->insert('download_images', $item);
        }

        public static function insertContent(array $content)
        {
           BackendModel::get('database')->insert('download_images_content', $content);
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
               'download_images', $item, 'id = ?', (int) $item['id']
           );
        }

        public static function updateContent(array $content, $id)
        {
           $db = BackendModel::get('database');
           foreach($content as $language => $row)
           {
               $db->update('download_images_content', $row, 'image_id = ? AND language = ?', array($id, $language));
           }
        }

        /**
        * Get the maximum Team sequence.
        *
        * @return int
        */
       public static function getMaximumSequence()
       {
           return (int) BackendModel::get('database')->getVar(
               'SELECT MAX(i.sequence)
                FROM download_images AS i'
           );
       }
}
