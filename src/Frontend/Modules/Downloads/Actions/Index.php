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
use Frontend\Core\Engine\Form as FrontendForm;
/**
 * This is the overview-action
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 * @author Davy Hellemans <davy.hellemans@netlash.com>
 */
class Index extends FrontendBaseBlock
{
    /**
     * The articles
     *
     * @var    array
     */
    private $items;

    /**
     * The pagination array
     * It will hold all needed parameters, some of them need initialization.
     *
     * @var    array
     */
    protected $pagination = array(
        'limit' => 10,
        'offset' => 0,
        'requested_page' => 1,
        'num_items' => null,
        'num_pages' => null
    );

    /**
     * Execute the extra
     */
    public function execute()
    {
        parent::execute();
        $this->setFilter();
        $this->loadTemplate();
        $this->loadForm();
        $this->validateForm();
        $this->getData();
        $this->parse();
    }

    private function loadForm()
    {
        // create the form
        $this->frm = new FrontendForm('downloadsIndexForm', null, 'get', null, false);
        $categories = FrontendDownloadsCategoriesModel::getForMultiCheckbox();
        if(!empty($categories)) $this->frm->addMultiCheckbox('categories', $categories);
        $this->frm->addCheckbox('resetFilter');
    }

     private function validateForm()
    {
        // is the form submitted
        if ($this->frm->isSubmitted()) {

            // no errors
            if ($this->frm->isCorrect()) {
                if($this->frm->getField('resetFilter')->isChecked()) $this->redirect(Navigation::getURLForBlock('Downloads') );
            }
        }
    }

    private function setFilter()
    {
        // set filter values
        $this->filter['categories'] = $this->URL->getParameter('categories', 'array');
        $this->filter['resetFilter'] = $this->URL->getParameter('resetFilter');
        $this->filter['form'] = $this->URL->getParameter('form', 'string');
    }

    /**
     * Load the data, don't forget to validate the incoming data
     */
    private function getData()
    {
        // requested page
        $requestedPage = $this->URL->getParameter('page', 'int', 1);

        // set URL and limit
        $this->pagination['url'] = Navigation::getURLForBlock('Downloads') . '?' . http_build_query($this->filter);
        $this->pagination['limit'] = $this->get('fork.settings')->get('Downloads', 'overview_number_of_items', 10);

        // populate count fields in pagination
        $this->pagination['num_items'] = FrontendDownloadsModel::getAllCount($this->filter);
        $this->pagination['num_pages'] = (int) ceil($this->pagination['num_items'] / $this->pagination['limit']);

        // num pages is always equal to at least 1
        if ($this->pagination['num_pages'] == 0) {
            $this->pagination['num_pages'] = 1;
        }

        // redirect if the request page doesn't exist
        if ($requestedPage > $this->pagination['num_pages'] || $requestedPage < 1) {
            $this->redirect(FrontendNavigation::getURL(404));
        }

        // populate calculated fields in pagination
        $this->pagination['requested_page'] = $requestedPage;
        $this->pagination['offset'] = ($this->pagination['requested_page'] * $this->pagination['limit']) - $this->pagination['limit'];

        // get articles
        $this->items = FrontendDownloadsModel::getAll($this->pagination['limit'], $this->pagination['offset'], $this->filter);
    }

    /**
     * Parse the data into the template
     */
    private function parse()
    {
        $this->frm->parse($this->tpl);

        // assign articles
        $this->tpl->assign('items', $this->items);

        $this->tpl->assign('searched', !($this->filter['form'] == NULL));

        // parse the pagination
        $this->parsePagination();
    }
}
