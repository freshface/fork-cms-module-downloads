<?php

namespace Backend\Modules\Downloads\Engine;

use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Language;

/**
 * In this file we store all generic functions that we will be using in the Downloads module
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Category
{

         const QRY_DATAGRID_BROWSE =
             'SELECT i.id, c.name,  i.sequence
              FROM downloads_categories AS i
              INNER JOIN downloads_category_content as c  on i.id = c.category_id
              WHERE c.language = ? AND i.parent_id = ? ORDER BY sequence';


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
                     INNER JOIN downloads_category_content AS m ON i.id = m.category_id
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
                     INNER JOIN downloads_category_content AS m ON i.id = m.category_id
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
           BackendModel::get('database')->delete('downloads_categories', 'id = ?', (int) $id);
           BackendModel::get('database')->delete('downloads_category_content', 'category_id = ?', (int) $id);
           BackendModel::get('database')->delete('downloads_linked_catgories', 'category_id = ?', (int) $id);
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
                FROM downloads_categories AS i
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
                FROM downloads_categories AS i
                WHERE i.id = ?',
               array((int) $id)
           );

           // data found
           $return['content'] = (array) $db->getRecords(
               'SELECT i.* FROM downloads_category_content AS i
               WHERE i.category_id = ?',
               array((int) $id), 'language');

           return  $return;

        }


        public static function getForDropdown()
         {
             $allCategories = (array) BackendModel::get('database')->getRecords('SELECT i.id, c.name, i.parent_id
              FROM downloads_categories AS i
              INNER JOIN downloads_category_content AS c on c.category_id = i.id
               WHERE c.language = ?
              ORDER BY i.sequence', array(Language::getWorkingLanguage()));

            $tree = array();
            $categoryTree = self::buildTree($tree, $allCategories);
            return $categoryTree;
         }




        public static function buildTree(array &$tree, array $categories, $parentId = 0, $level = 0)
        {
            foreach ($categories as $category) {
                if ($category['parent_id'] == $parentId) {
                    $tree[$category['id']] = str_repeat('-', $level*2) . ' ' . $category['name'];
                    $level++;
                    $children = self::buildTree($tree, $categories, $category['id'], $level);
                    $level--;
                }
            }
            return $tree;
        }

        public static function getForMultiCheckbox()
         {
             $allCategories = (array) BackendModel::get('database')->getRecords('SELECT i.id, c.name, i.parent_id
              FROM downloads_categories AS i
              INNER JOIN downloads_category_content AS c on c.category_id = i.id
              WHERE c.language = ?
              ORDER BY i.sequence', array(Language::getWorkingLanguage()));

            $tree = array();
            $categoryTree = self::buildTreeForMultiCheckbox($tree, $allCategories);

            return $categoryTree;
         }

        public static function buildTreeForMultiCheckbox(array &$tree, array $categories, $parentId = 0, $level = 0)
        {
            foreach ($categories as $category) {
                if ($category['parent_id'] == $parentId) {

                    $prefix = '';
                    if($parentId) {
                        $prefix = $tree[$parentId]['label'] . ' › ';
                    }

                    $tree[$category['id']] = array('label' => $prefix . $category['name'], 'value' =>  $category['id']);
                    $level++;
                    $children = self::buildTreeForMultiCheckbox($tree, $categories, $category['id'], $level);
                    $level--;
                }
            }
            return $tree;
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

           return (int) BackendModel::get('database')->insert('downloads_categories', $item);
        }

        public static function insertContent(array $content)
        {
            foreach($content as &$item){

                if( BackendModel::get('fork.settings')->get('Downloads', 'make_widget_per_category', true) == true)
                {
                    $data = [
                        'id' => $item['category_id'],
                        'language' => $item['language'],
                        'extra_label' => 'Category: ' . $item['name'],
                    ];

                    $item['extra_id'] = BackendModel::insertExtra(
                        'widget',
                        'Downloads',
                        'Category',
                        'Category',
                        $data
                    );
                }

                BackendModel::get('database')->insert('downloads_category_content', $item);
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
               'downloads_categories', $item, 'id = ?', (int) $item['id']
           );
        }

        public static function updateContent(array $content, $id)
        {

           $db = BackendModel::get('database');
           foreach($content as $language => $row)
           {
               if( BackendModel::get('fork.settings')->get('Downloads', 'make_widget_per_category', true) == true && $row['extra_id'])
               {
                   $data = [
                       'id' => $row['category_id'],
                       'language' => $row['language'],
                       'extra_label' => 'Category: ' . $row['name'],
                   ];

                   BackendModel::updateExtra($row['extra_id'], 'data', $data);
               }

               $db->update('downloads_category_content', $row, 'category_id = ? AND language = ?', array($id, $language));
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
                FROM downloads_categories AS i'
           );
       }
}
