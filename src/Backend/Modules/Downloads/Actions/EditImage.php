<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionEdit;
use Backend\Core\Engine\Form;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\Downloads\Engine\Category as BackendDownloadsCategoryModel;
use Backend\Modules\Downloads\Engine\Images as BackendDownloadsImagesModel;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;

use Backend\Modules\SiteHelpers\Engine\Helper as SiteHelpersHelper;
use Backend\Modules\SiteHelpers\Engine\Model as SiteHelpersModel;
use Backend\Modules\SiteHelpers\Engine\Assets as SiteHelpersAssets;
use Common\Uri as CommonUri;

use Backend\Core\Engine\DataGridDB as BackendDataGridDB;

/**
 * This is the edit-action, it will display a form with the item data to edit
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class EditImage extends ActionEdit
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
        if ($this->id == null || !BackendDownloadsImagesModel::exists($this->id)) {
            $this->redirect(
                Model::createURLForAction('Index') . '&error=non-existing'
            );
        }

        $this->record = BackendDownloadsImagesModel::get($this->id);
    }

    /**
     * Load the form
     */
    protected function loadForm()
    {
        // create form
        $this->frm = new Form('edit');

        $this->frm->addImage('image');


        // set hidden values
        $rbtHiddenValues[] = array('label' => Language::lbl('Hidden', $this->URL->getModule()), 'value' => 'Y');
        $rbtHiddenValues[] = array('label' => Language::lbl('Published'), 'value' => 'N');

        $this->frm->addRadiobutton('hidden', $rbtHiddenValues, $this->record['hidden']);


        foreach ($this->languages as &$language) {
            $field = $this->frm->addText('name_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['name']) ? $this->record['content'][$language['abbreviation']]['name'] : '', null, 'form-control title', 'form-control danger title');
            $language['name_field'] = $field->parse();
            $language['name_errors'] = $field->getErrors();

            $field = $this->frm->addEditor('description_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['description']) ? $this->record['content'][$language['abbreviation']]['description'] : '');
            $language['description_field'] = $field->parse();
            $language['description_errors'] = $field->getErrors();

            $url = Model::getURLForBlock($this->URL->getModule(), 'Detail', $language['abbreviation']);
            $url404 = Model::getURL(404, $language['abbreviation']);
            $language['slug'] = isset($this->record['content'][$language['abbreviation']]['url']) ? $this->record['content'][$language['abbreviation']]['url'] : '';
            if ($url404 != $url) {
                $language['url'] = SITE_URL . $url;
            }
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

            SiteHelpersHelper::validateImage($this->frm, 'image');

            if ($this->frm->isCorrect()) {
                $item['id'] = $this->id;
                $item['hidden'] = $fields['hidden']->getValue();
                $imagePath = SiteHelpersHelper::generateFolders($this->getModule(), 'images');

                // image provided?
                if ($fields['image']->isFilled()) {

                    // replace old image
                    if ($this->record['filename']) {
                        $item['filename'] = null;
                        Model::deleteThumbnails(FRONTEND_FILES_PATH . '/' . $this->getModule() . '/images', $this->record['filename']);
                    }

                    // build the image name
                    $item['filename'] = uniqid() . '.' . $fields['image']->getExtension();

                    // upload the image & generate thumbnails
                    $fields['image']->generateThumbnails($imagePath, $item['filename']);
                }

                $content = array();

                foreach ($this->languages as $language) {
                    $specific['image_id'] = $item['id'];
                    $specific['language'] = $language['abbreviation'];
                    $specific['name'] = $this->frm->getField('name_' . $language['abbreviation'])->getValue();
                    $specific['url'] =  BackendDownloadsImagesModel::getURL(CommonUri::getUrl($specific['name']), $language['abbreviation'], $item['id']);
                    $specific['description'] = ($this->frm->getField('description_' . $language['abbreviation'])->isFilled()) ? $this->frm->getField('description_' . $language['abbreviation'])->getValue() : null;
                    $content[$language['abbreviation']] = $specific;
                }

                BackendDownloadsImagesModel::update($item);
                BackendDownloadsImagesModel::updateContent($content, $item['id']);

                Model::triggerEvent(
                    $this->getModule(), 'after_edit', $item
                );
                $this->redirect(
                    Model::createURLForAction('EditImage') . '&report=edited&id=' . $item['id']
                );
            }
        }
    }
}
