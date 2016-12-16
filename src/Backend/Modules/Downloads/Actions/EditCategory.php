<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionEdit;
use Backend\Core\Engine\Form;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\Downloads\Engine\Category as BackendDownloadsCategoryModel;

use Backend\Modules\Search\Engine\Model as BackendSearchModel;

use Backend\Modules\SiteHelpers\Engine\Helper as SiteHelpersHelper;
use Common\Uri as CommonUri;

/**
 * This is the edit-action, it will display a form with the item data to edit
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class EditCategory extends ActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        $this->languages = SiteHelpersHelper::getActiveLanguages();

        $this->loadData();
        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    /**
     * Load the item data
     */
    protected function loadData()
    {
        $this->id = $this->getParameter('id', 'int', null);
        if ($this->id == null || !BackendDownloadsCategoryModel::exists($this->id)) {
            $this->redirect(
                Model::createURLForAction('Index') . '&error=non-existing'
            );
        }

        $this->record = BackendDownloadsCategoryModel::get($this->id);
    }

    /**
     * Load the form
     */
    protected function loadForm()
    {
        // create form
        $this->frm = new Form('edit');


        // set hidden values
        $rbtHiddenValues[] = array('label' => Language::lbl('Hidden', $this->URL->getModule()), 'value' => 'Y');
        $rbtHiddenValues[] = array('label' => Language::lbl('Published'), 'value' => 'N');

        $this->frm->addRadiobutton('hidden', $rbtHiddenValues, $this->record['hidden']);

        $categories = BackendDownloadsCategoryModel::getForDropdown();
        $this->frm->addDropdown('parent_id', $categories, $this->record['parent_id'])->setDefaultElement('', 0);

        foreach ($this->languages as &$language) {
            $field = $this->frm->addText('name_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['name']) ? $this->record['content'][$language['abbreviation']]['name'] : '', null, 'form-control title', 'form-control danger title');
            $language['name_field'] = $field->parse();
            $language['name_errors'] = $field->getErrors();

            $field = $this->frm->addEditor('description_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['description']) ? $this->record['content'][$language['abbreviation']]['description'] : '');
            $language['description_field'] = $field->parse();
            $language['description_errors'] = $field->getErrors();
        }
    }

    /**
     * Parse the page
     */
    protected function parse()
    {
        parent::parse();

        $this->tpl->assign('languages', $this->languages);
        $this->tpl->assign('record', $this->record);
        $this->tpl->assign('allow_subcategories', $this->get('fork.settings')->get($this->getModule(), 'allow_subcategories', false));
    }

    /**
     * Validate the form
     */
    protected function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->frm->cleanupFields();

            // validation
            $fields = $this->frm->getFields();

            foreach ($this->languages as $key => $language) {
                $field = $this->frm->getField('name_' . $this->languages[$key]['abbreviation'])->isFilled(Language::getError('FieldIsRequired'));
                $this->languages [$key]['name_errors'] = $this->frm->getField('name_' . $this->languages[$key]['abbreviation'])->getErrors();
            }


            if ($this->frm->isCorrect()) {
                $item['id'] = $this->id;
                $item['parent_id']  = $fields['parent_id']->getValue();
                $item['hidden'] = $fields['hidden']->getValue();

                if ($item['parent_id'] == 0) {
                    $item['path'] = '/' . $item['id'] . '/';
                } else {
                    $category = BackendDownloadsCategoryModel::get($item['parent_id']);
                    $item['path'] =  $category['path'] . $item['id'] . '/';
                }


                $content = array();

                foreach ($this->languages as $language) {
                    $specific['extra_id'] = $this->record['content'][$language['abbreviation']]['extra_id'];
                    $specific['category_id'] = $item['id'];
                    $specific['language'] = $language['abbreviation'];
                    $specific['name'] = $this->frm->getField('name_' . $language['abbreviation'])->getValue();
                    $specific['url'] =  BackendDownloadsCategoryModel::getURL(CommonUri::getUrl($specific['name']), $language['abbreviation'], $item['id']);
                    $specific['description'] = ($this->frm->getField('description_' . $language['abbreviation'])->isFilled()) ? $this->frm->getField('description_' . $language['abbreviation'])->getValue() : null;
                    $content[$language['abbreviation']] = $specific;

                    BackendSearchModel::saveIndex(
                        $this->getModule(), $item['id'],
                        array('name' => $specific['name'], 'description' => $specific['description']),
                        $language['abbreviation']
                    );
                }

                BackendDownloadsCategoryModel::update($item);
                BackendDownloadsCategoryModel::updateContent($content, $item['id']);

                Model::triggerEvent(
                    $this->getModule(), 'after_category_edit', $item
                );
                $this->redirect(
                    Model::createURLForAction('EditCategory') . '&report=edited&id=' . $item['id']
                );
            }
        }
    }
}
