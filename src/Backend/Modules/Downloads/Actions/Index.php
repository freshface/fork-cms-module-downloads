<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionIndex;
use Backend\Core\Engine\Authentication;
use Backend\Core\Engine\DataGridDB;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Core\Engine\Form;
use Backend\Modules\Downloads\Engine\Category as BackendDownloadsCategoryModel;

/**
 * This is the index-action (default), it will display the overview of Downloads posts
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Index extends ActionIndex
{
    private $filter = [];

    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        $this->setFilter();
        $this->loadForm();

        $this->loadDataGridDownloads();
        $this->loadDataGridDownloadsDrafts();
        $this->parse();
        $this->display();
    }

    /**
     * Load the dataGrid
     */
    protected function loadDataGridDownloads()
    {
        $query = 'SELECT i.id, c.name,  i.sequence, i.hidden
         FROM downloads AS i
         INNER JOIN download_content as c  on i.id = c.download_id';

        if (isset($this->filter['categories']) && $this->filter['categories'] !== null && count($this->filter['categories'])) {
            $query .= ' INNER JOIN downloads_linked_catgories AS cat ON i.id = cat.download_id';
        }

        $query .= ' WHERE 1';

        $parameters = array();
        $query .= ' AND c.language = ?';
        $parameters[] = Language::getWorkingLanguage();

        $query .= ' AND i.status = ?';
        $parameters[] = 'active';

        if ($this->filter['value']) {
            $query .= ' AND c.name LIKE ?';
            $parameters[] = '%' . $this->filter['value'] . '%';
        }

        if (isset($this->filter['categories']) && $this->filter['categories'] !== null && count($this->filter['categories'])) {
            $query .= ' AND cat.category_id IN(' . implode(',', array_values($this->filter['categories'])) . ')';
        }

        $query .= 'GROUP BY i.id ORDER BY sequence DESC';

        $this->dataGridDownloads = new DataGridDB(
            $query,
            $parameters
        );

        $this->dataGridDownloads->enableSequenceByDragAndDrop();
        $this->dataGridDownloads->setURL($this->dataGridDownloads->getURL() . '&' . http_build_query($this->filter));

        $this->dataGridDownloads->setColumnAttributes(
            'name', array('class' => 'title')
        );

        // check if this action is allowed
        if (Authentication::isAllowedAction('Edit')) {
            $this->dataGridDownloads->addColumn(
                'edit', null, Language::lbl('Edit'),
                Model::createURLForAction('Edit') . '&amp;id=[id]',
                Language::lbl('Edit')
            );
            $this->dataGridDownloads->setColumnURL(
                'name', Model::createURLForAction('Edit') . '&amp;id=[id]'
            );
        }
    }

    /**
     * Load the dataGrid
     */
    protected function loadDataGridDownloadsDrafts()
    {
        $query = 'SELECT i.id, c.name,  i.sequence, i.hidden
         FROM downloads AS i
         INNER JOIN download_content as c  on i.id = c.download_id';

        if (isset($this->filter['categories']) && $this->filter['categories'] !== null && count($this->filter['categories'])) {
            $query .= ' INNER JOIN downloads_linked_catgories AS cat ON i.id = cat.download_id';
        }

        $query .= ' WHERE 1';

        $parameters = array();
        $query .= ' AND c.language = ?';
        $parameters[] = Language::getWorkingLanguage();

        $query .= ' AND i.status = ?';
        $parameters[] = 'draft';



        if ($this->filter['value']) {
            $query .= ' AND c.name LIKE ?';
            $parameters[] = '%' . $this->filter['value'] . '%';
        }

        if (isset($this->filter['categories']) && $this->filter['categories'] !== null && count($this->filter['categories'])) {
            $query .= ' AND cat.category_id IN(' . implode(',', array_values($this->filter['categories'])) . ')';
        }


        $query .= 'GROUP BY i.id ORDER BY sequence DESC';

        $this->dataGridDownloadsDrafts = new DataGridDB(
            $query,
            $parameters
        );

        $this->dataGridDownloadsDrafts->enableSequenceByDragAndDrop();
        $this->dataGridDownloadsDrafts->setURL($this->dataGridDownloadsDrafts->getURL() . '&' . http_build_query($this->filter));

        $this->dataGridDownloads->setColumnAttributes(
            'name', array('class' => 'title')
        );

        // check if this action is allowed
        if (Authentication::isAllowedAction('Edit')) {
            $this->dataGridDownloadsDrafts->addColumn(
                'edit', null, Language::lbl('Edit'),
                Model::createURLForAction('Edit') . '&amp;id=[id]',
                Language::lbl('Edit')
            );
            $this->dataGridDownloadsDrafts->setColumnURL(
                'name', Model::createURLForAction('Edit') . '&amp;id=[id]'
            );
        }
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        $this->frm = new Form('filter', Model::createURLForAction(), 'get');

        $categories = BackendDownloadsCategoryModel::getForMultiCheckbox();

        $this->frm->addText('value', $this->filter['value']);

        if (!empty($categories) && Authentication::isAllowedAction('AddCategory')) {
            $this->frm->addMultiCheckbox(
                'categories',
                $categories,
                '',
                'noFocus'
            );
        }

        // manually parse fields
        $this->frm->parse($this->tpl);
    }


    /**
     * Sets the filter based on the $_GET array.
     */
    private function setFilter()
    {
        $this->filter['categories'] = $this->getParameter('categories', 'array');
        $this->filter['value'] = $this->getParameter('value') == null ? '' : $this->getParameter('value');
    }


    /**
     * Parse the page
     */
    protected function parse()
    {
        // parse the dataGrid if there are results
        $this->tpl->assign('dataGridDownloads', (string) $this->dataGridDownloads->getContent());
        $this->tpl->assign('dataGridDownloadsDraft', (string) $this->dataGridDownloadsDrafts->getContent());
    }
}
