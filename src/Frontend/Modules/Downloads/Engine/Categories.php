<?php

namespace Frontend\Modules\Downloads\Engine;

use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Core\Engine\Language;
use Frontend\Core\Engine\Navigation;

/**
 * In this file we store all generic functions that we will be using in the Downloads module
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Categories
{


    /**
    * Get the number of items
    *
    * @return int
    */
   public static function getAllCount($filter = array())
   {
       $query =
            'SELECT COUNT(i.id) AS count
             FROM downloads AS i';

      // init parameters
      $parameters = array();

       if ($filter['categories'] !== null) {
           $query .= ' INNER JOIN downloads_linked_catgories AS c ON i.id = c.download_id';
       }

       $query .= ' WHERE 1';

       $query .= ' AND i.hidden = ?';
       $parameters[] = 'N';

       $query .= ' AND i.status = ?';
       $parameters[] = 'active';

       $query .= ' AND i.publish_on <= ?';
       $parameters[] = FrontendModel::getUTCDate('Y-m-d H:i') . ':00';

       if ($filter['categories'] !== null) {
           $query .= ' AND c.category_id IN(' . implode(',', array_values($filter['categories'])) . ')';
       }

      //$query .= ' GROUP BY i.id';

      return (int) FrontendModel::get('database')->getVar($query, $parameters);
   }

    public static function get($URL)
    {
        $URL = (string) $URL;
        $item = (array) FrontendModel::getContainer()->get('database')->getRecord(
           'SELECT i.id, c.name, c.url, c.intro, i.path
            FROM downloads_categories AS i
            JOIN downloads_category_content AS c on c.category_id = i.id
            WHERE c.url = ? AND c.language = ? AND i.hidden = ?',
           array(
               $URL,
               FRONTEND_LANGUAGE,
               'N'
           )
       );
       // no results?
       if (empty($item)) {
           return array();
       }

       // init var
       $link = Navigation::getURLForBlock('Downloads', 'Category');
        $item['full_url'] = $link . '/' . $item['url'];
       //$item['images'] = FrontendDownloadsImagesModel::getAll($item['id']);

       // return
       return $item;
    }

    public static function getAllChildrenByPath($path)
    {
        $path = (string) $path;
        $items = (array) FrontendModel::getContainer()->get('database')->getRecords(
           'SELECT i.id, c.name, c.url, c.description, i.path
            FROM downloads_categories AS i
            JOIN downloads_category_content AS c on c.category_id = i.id
            JOIN downloads_linked_catgories AS pc on pc.category_id = i.id
            JOIN downloads as p on p.id = pc.download_id
            WHERE i.path != ? AND i.path LIKE ? AND c.language = ? AND i.hidden = ? AND p.hidden = ? AND p.publish_on <= ?',
           array(
               $path ,
               $path . '%',
               FRONTEND_LANGUAGE,
               'N',
               'N',
               FrontendModel::getUTCDate('Y-m-d H:i') . ':00'
           ), 'id'
       );

       // no results?
       if (empty($items)) {
           return array();
       }

       // init var
       $link = Navigation::getURLForBlock('Downloads', 'Category');

        foreach ($items as &$item) {
            $item['full_url'] = $link . '/' . $item['url'];
        }

       // return
       return $items;
    }

    public static function getAllChildrenForDownload($download_id)
    {
        $download_id = (int) $download_id;
        $items = (array) FrontendModel::getContainer()->get('database')->getRecords(
           'SELECT i.id, c.name, c.url, c.description, i.path
            FROM downloads_categories AS i
            JOIN downloads_category_content AS c on c.category_id = i.id
            JOIN downloads_linked_catgories AS pc on pc.category_id = i.id
            JOIN downloads as p on p.id = pc.download_id
            WHERE  i.parent_id != ? AND pc.download_id = ? AND c.language = ? AND i.hidden = ? AND p.hidden = ? AND p.publish_on <= ? GROUP BY i.id',
           array(
              0,
               $download_id ,
               FRONTEND_LANGUAGE,
               'N',
               'N',
               FrontendModel::getUTCDate('Y-m-d H:i') . ':00'
           ), 'id'
       );

       // no results?
       if (empty($items)) {
           return array();
       }

        $parents = self::getAll(array('parent_id' => 0));


       // init var
       $link = Navigation::getURLForBlock('Downloads', 'Category');

        foreach ($items as &$item) {
            $item['full_url'] = $link . '/' . $item['url'];
            $pathArray = explode('/', rtrim(ltrim($item['path'], '/'), '/'));
            $item['full_filter_url'] = $parents[$pathArray[0]]['full_url'] . '?form=downloadsIndexForm&categories[]=' . $item['id'];
        }

       // return
       return $items;
    }


    public static function getForMultiCheckbox()
    {
        $db = FrontendModel::get('database');

        return (array) $db->getRecords(
            'SELECT i.id as value, co.name AS label
             FROM downloads_categories AS i
              JOIN downloads_category_content as co on co.category_id = i.id
             INNER JOIN downloads_linked_catgories AS c on i.id = c.category_id
             INNER JOIN downloads AS p on c.download_id = p.id GROUP BY i.id ORDER BY i.sequence', array());
    }

    public static function getForMultiCheckboxForParent($parent_id)
    {
        $db = FrontendModel::get('database');

        return (array) $db->getRecords(
            'SELECT i.id as value, co.name AS label
             FROM downloads_categories AS i
              JOIN downloads_category_content as co on co.category_id = i.id
             INNER JOIN downloads_linked_catgories AS c on i.id = c.category_id
             INNER JOIN downloads AS p on c.download_id = p.id
             WHERE i.parent_id = ? AND co.language = ?
             GROUP BY i.id ORDER BY i.sequence', array( (int) $parent_id , FRONTEND_LANGUAGE));
    }

     /**
     * Get all items (at least a chunk)
     *
     * @param int $limit  The number of items to get.
     * @param int $offset The offset.
     * @return array
     */
    public static function getAll($filter = array())
    {
        $query = 'SELECT i.id,  co.name, co.url, co.description
             FROM downloads_categories AS i
             JOIN downloads_category_content AS co on co.category_id = i.id';

        // init parameters
        $parameters = array();


        $query .= ' WHERE 1';

        $query .= ' AND i.hidden = ?';
        $parameters[] = 'N';



        $query .= ' AND co.language = ?';
        $parameters[] = FRONTEND_LANGUAGE;



        if (isset($filter['parent_id']) && $filter['parent_id'] !== null) {
            $query .= ' AND i.parent_id = ?';
            $parameters[] = $filter['parent_id'];
        }


        $query .= ' GROUP BY i.id ORDER BY i.sequence ASC';


        $items = (array) FrontendModel::get('database')->getRecords($query, $parameters, 'id');

        // no results?
        if (empty($items)) {
            return array();
        }

        // get detail action url
        $detailUrl = Navigation::getURLForBlock('Downloads', 'Category');

        // prepare items for search
        foreach ($items as &$item) {
            $item['full_url'] =  $detailUrl . '/' . $item['url'];
        }


        // return
        return $items;
    }
}
