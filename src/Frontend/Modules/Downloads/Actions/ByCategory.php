<?php

namespace Frontend\Modules\Downloads\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Frontend\Core\Engine\Base\Block as FrontendBaseBlock;
use Frontend\Core\Engine\Navigation as Navigation;
use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Modules\Downloads\Engine\Model as FrontendDownloadsModel;
use Frontend\Modules\Downloads\Engine\Categories as FrontendDownloadsCategoriesModel;

/**
 * This is the overview-action
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 * @author Davy Hellemans <davy.hellemans@netlash.com>
 */
class ByCategory extends FrontendBaseBlock
{
    /**
     * The articles
     *
     * @var    array
     */
    private $items;


    /**
     * Execute the extra
     */
    public function execute()
    {
        parent::execute();
        $this->loadTemplate();
        $this->getData();
        $this->parse();
    }

    /**
     * Load the data, don't forget to validate the incoming data
     */
    private function getData()
    {
        // get articles
        $this->categories = FrontendDownloadsCategoriesModel::getAll();

        foreach ($this->categories as &$category) {
            $category['items'] = FrontendDownloadsModel::getAll(100, 0, array('categories' => array($category['id'])));
        }
    }

    /**
     * Parse the data into the template
     */
    private function parse()
    {
        // assign articles
        $this->tpl->assign('categories', $this->categories);
    }
}
