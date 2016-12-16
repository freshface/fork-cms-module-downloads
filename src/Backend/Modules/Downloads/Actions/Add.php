<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionAdd;
use Backend\Core\Engine\Form;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\Downloads\Engine\Category as BackendDownloadsCategoryModel;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;

use Backend\Modules\SiteHelpers\Engine\Helper as SiteHelpersHelper;
use Backend\Modules\SiteHelpers\Engine\Model as SiteHelpersModel;
use Backend\Modules\SiteHelpers\Engine\Assets as SiteHelpersAssets;
use Common\Uri as CommonUri;
use Backend\Core\Engine\Authentication;
use Backend\Modules\Tags\Engine\Model as BackendTagsModel;

/**
 * This is the add-action, it will display a form to create a new item
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Add extends ActionAdd
{
    /**
     * Execute the actions
     */
    public function execute()
    {
        parent::execute();

        $this->languages = SiteHelpersHelper::getActiveLanguages();
        //SiteHelpersAssets::addSelect2($this->header);


        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    /**
     * Load the form
     */
    protected function loadForm()
    {
        $this->frm = new Form('add');

        $this->frm->addImage('image');

        // set hidden values
        $rbtHiddenValues[] = array('label' => Language::lbl('Hidden', $this->URL->getModule()), 'value' => 'Y');
        $rbtHiddenValues[] = array('label' => Language::lbl('Published'), 'value' => 'N');

        $this->frm->addRadiobutton('hidden', $rbtHiddenValues, 'N');

        $this->frm->addDate('publish_on_date');
        $this->frm->addTime('publish_on_time');

        $this->frm->addCheckbox('require_details');
        $this->frm->addCheckbox('download_widget');

        // set size values
        $rbtSizeValues[] = array('label' => Language::getLabel('Small'), 'value' => 'small');
        $rbtSizeValues[] = array('label' => Language::getLabel('Medium'), 'value' => 'medium');
        $rbtSizeValues[] = array('label' => Language::getLabel('Large'), 'value' => 'large');
        $this->frm->addRadiobutton('size', $rbtSizeValues);

        //$this->categories = BackendDownloadsCategoryModel::getForDropdown();
        //$this->frm->addDropdown('categories', $this->categories, null, true, 'select select2', 'selectError select2');

       $this->categories = BackendDownloadsCategoryModel::getForMultiCheckbox();
        if (!empty($this->categories) && Authentication::isAllowedAction('AddCategory')) {
            $this->frm->addMultiCheckbox('categories', $this->categories);
        }

        foreach ($this->languages as &$language) {
            $field = $this->frm->addText('name_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['name']) ? $this->record['content'][$language['abbreviation']]['name'] : '', null, 'form-control title', 'form-control danger title');
            $language['name_field'] = $field->parse();

            $field = $this->frm->addEditor('description_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['description']) ? $this->record['content'][$language['abbreviation']]['description'] : '');
            $language['description_field'] = $field->parse();

            $field = $this->frm->addText('tags_' . $language['abbreviation'], '', null, 'form-control', 'form-control danger');
            $language['tags_field'] = $field->parse();

            $field = $this->frm->addCheckbox('seo_url_overwrite_' . $language['abbreviation']);
            $language['seo_url_overwrite_field'] = $field->parse();

            $field = $this->frm->addCheckbox('seo_description_overwrite_' . $language['abbreviation']);
            $language['seo_description_overwrite_field'] = $field->parse();

            $field = $this->frm->addCheckbox('seo_title_overwrite_' . $language['abbreviation']);
            $language['seo_title_overwrite_field'] = $field->parse();

            $field = $this->frm->addText('url_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['url']) ? $this->record['content'][$language['abbreviation']]['url'] : '');
            $language['url_field'] = $field->parse();

            $field = $this->frm->addFile('file_' . $language['abbreviation']);
            $language['file_field'] = $field->parse();

            $field = $this->frm->addText('seo_title_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['seo_title']) ? $this->record['content'][$language['abbreviation']]['seo_title'] : '');
            $language['seo_title_field'] = $field->parse();

            $field = $this->frm->addText('seo_description_' . $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['seo_description']) ? $this->record['content'][$language['abbreviation']]['seo_description'] : '');
            $language['seo_description_field'] = $field->parse();

            $url = Model::getURLForBlock($this->URL->getModule(), 'Detail', $language['abbreviation']);
            $url404 = Model::getURL(404, $language['abbreviation']);
            $language['slug'] = '';
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
        $this->tpl->assign('allow_tags', $this->get('fork.settings')->get($this->getModule(), 'allow_tags'));
    }

    /**
     * Validate the form
     */
    protected function validateForm()
    {
        if ($this->frm->isSubmitted()) {

            // get the status
            $status = \SpoonFilter::getPostValue('status', array('active', 'draft'), 'active');

            $this->frm->cleanupFields();

            // validation
            $fields = $this->frm->getFields();

            SiteHelpersHelper::validateImage($this->frm, 'image');

            $this->frm->getField('publish_on_date')->isValid(Language::err('DateIsInvalid'));
            $this->frm->getField('publish_on_time')->isValid(Language::err('TimeIsInvalid'));


            foreach ($this->languages as $key => $language) {
                $field = $this->frm->getField('name_' . $this->languages[$key]['abbreviation'])->isFilled(Language::getError('FieldIsRequired'));
                $this->languages [$key]['name_errors'] = $this->frm->getField('name_' . $this->languages[$key]['abbreviation'])->getErrors();
            }

            if ($this->frm->isCorrect()) {
                // build the item
                $item['hidden'] = $fields['hidden']->getValue();
                $item['sequence'] = BackendDownloadsModel::getMaximumSequence() + 1;
                $item['publish_on'] = Model::getUTCDate(null, Model::getUTCTimestamp($this->frm->getField('publish_on_date'), $this->frm->getField('publish_on_time')));
                $item['status'] = $status;
                $item['size'] = $fields['size']->getValue();

                $item['require_details'] = $fields['require_details']->isChecked() ? 'Y' : 'N';
                $item['download_widget'] = $fields['download_widget']->isChecked() ? 'Y' : 'N';

                $imagePath = SiteHelpersHelper::generateFolders($this->getModule());


                // image provided?
                if ($fields['image']->isFilled()) {
                    // build the image name
                    $item['image'] = uniqid() . '.' . $fields['image']->getExtension();

                    // upload the image & generate thumbnails
                    $fields['image']->generateThumbnails($imagePath, $item['image'], 0777);
                }

                $item['id'] = BackendDownloadsModel::insert($item);

                if (!empty($this->categories) && Authentication::isAllowedAction('AddCategory')) {
                    SiteHelpersModel::insertLinked(
                        $this->frm->getField('categories')->getValue(),
                        'category_id',
                        $item['id'],
                        'download_id',
                        'downloads_linked_catgories'
                    );
                }

                $content = array();


                foreach ($this->languages as $language) {
                    $specific['download_id'] = $item['id'];

                    $specific['language'] = $language['abbreviation'];
                    $specific['name'] = $this->frm->getField('name_' . $language['abbreviation'])->getValue();
                    $specific['description'] = $this->frm->getField('description_' . $language['abbreviation'])->getValue() ? $this->frm->getField('description_' . $language['abbreviation'])->getValue() : null;

                    $specific['seo_url_overwrite'] = $this->frm->getField('seo_url_overwrite_' . $language['abbreviation'])->isChecked() ? 'Y' : 'N';
                    $specific['seo_description_overwrite'] = $this->frm->getField('seo_description_overwrite_' . $language['abbreviation'])->isChecked() ? 'Y' : 'N';
                    $specific['seo_title_overwrite'] = $this->frm->getField('seo_title_overwrite_' . $language['abbreviation'])->isChecked() ? 'Y' : 'N';

                    $specific['url'] =  BackendDownloadsModel::getURL(CommonUri::getUrl($specific['name']), $language['abbreviation']);
                    if ($specific['seo_url_overwrite'] == 'Y') {
                        $specific['url'] = BackendDownloadsModel::getURL(CommonUri::getUrl($this->frm->getField('url_' . $language['abbreviation'])->getValue()), $language['abbreviation']);
                    }

                    $specific['seo_description'] = $specific['name'];
                    if ($specific['seo_description_overwrite'] == 'Y') {
                        $specific['seo_description'] = $this->frm->getField('seo_description_' . $language['abbreviation'])->getValue() ? $this->frm->getField('seo_description_' . $language['abbreviation'])->getValue() : null;
                    }

                    $specific['seo_title'] = $specific['name'];
                    if ($specific['seo_title_overwrite'] == 'Y') {
                        $specific['seo_title'] = $this->frm->getField('seo_title_' . $language['abbreviation'])->getValue() ? $this->frm->getField('seo_title_' . $language['abbreviation'])->getValue() : null;
                    }


                    // file
                    if ($this->frm->getField('file_' . $language['abbreviation'])->isFilled()) {
                        $specific['file'] =  $specific['url']
                        . '-' . $specific['language']
                        . '.' . $this->frm->getField('file_' . $language['abbreviation'])->getExtension();

                        $specific['file_extensions'] = $this->frm->getField('file_' . $language['abbreviation'])->getExtension();
                        $specific['file_size'] = $this->frm->getField('file_' . $language['abbreviation'])->getFileSize('mb');

                        $this->frm->getField('file_' . $language['abbreviation'])->moveFile(FRONTEND_FILES_PATH . '/Downloads/file/' . $specific['file'], '0775');
                    }

                    $content[$language['abbreviation']] = $specific;

                    // save tags
                    BackendTagsModel::saveTags($item['id'], $this->frm->getField('tags_' . $language['abbreviation'])->getValue(), $this->URL->getModule(), $language['abbreviation']);

                    BackendSearchModel::saveIndex(
                        $this->getModule(), $item['id'],
                        array('name' => $specific['name'], 'description' => $specific['description']),
                        $language['abbreviation']
                    );
                }

                // insert it
               BackendDownloadsModel::insertContent($content, $item);

                Model::triggerEvent(
                    $this->getModule(), 'after_add', $item
                );
                $this->redirect(
                    Model::createURLForAction('Edit') . '&report=added&id=' . $item['id']
                );
            }
        }
    }
}
