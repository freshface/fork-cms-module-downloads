<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionIndex;
use Backend\Core\Engine\Authentication;
use Backend\Core\Engine\DataGridDB;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\Downloads\Engine\Category as BackendDownloadsCategoryModel;

/**
 * This is the index-action (default), it will display the overview of Downloads posts
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Categories extends ActionIndex
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();
        $this->loadDataGrid();

        $this->parse();
        $this->display();
    }

    /**
     * Load the dataGrid
     */
    protected function loadDataGrid()
    {
        $this->parent_id = \SpoonFilter::getGetValue('parent_id', null, null, 'int');
        //$this->parent_id = $this->parent_id  == 0 ? null : $this->parent_id;

        $this->dataGrid = new DataGridDB(
            BackendDownloadsCategoryModel::QRY_DATAGRID_BROWSE,
            array(Language::getWorkingLanguage(), $this->parent_id )
        );

        $this->dataGrid->enableSequenceByDragAndDrop();
        $this->dataGrid->setAttributes(array('data-action' => "SequenceCategories"));

        $this->dataGrid->setColumnAttributes(
            'name', array('class' => 'title')
        );

        if ($this->get('fork.settings')->get($this->getModule(), 'allow_subcategories', false) == true) {
            $this->dataGrid->addColumn(
                  'children', null, Language::lbl('ShowSubcategories'),
                  Model::createURLForAction('Categories') . '&amp;parent_id=[id]',
                  Language::lbl('ShowSubcategories')
              );
        }

        // check if this action is allowed
        if (Authentication::isAllowedAction('EditCategory')) {
            $this->dataGrid->addColumn(
                'edit', null, Language::lbl('Edit'),
                Model::createURLForAction('EditCategory') . '&amp;id=[id]',
                Language::lbl('Edit')
            );
            $this->dataGrid->setColumnURL(
                'name', Model::createURLForAction('EditCategory') . '&amp;id=[id]'
            );
        }
    }

    /**
     * Parse the page
     */
    protected function parse()
    {
        // parse the dataGrid if there are results
        $this->tpl->assign('dataGrid', (string) $this->dataGrid->getContent());
    }
}
