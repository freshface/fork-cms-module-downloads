<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionEdit;
use Backend\Core\Engine\Form;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\Downloads\Engine\Category as BackendDownloadsCategoryModel;
use Backend\Modules\Downloads\Engine\Images as BackendDownloadsImagesModel;
use Backend\Modules\Downloads\Engine\Entries as BackendDownloadsEntriesModel;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;

use Backend\Modules\SiteHelpers\Engine\Helper as SiteHelpersHelper;
use Backend\Modules\SiteHelpers\Engine\Model as SiteHelpersModel;
use Backend\Modules\SiteHelpers\Engine\Assets as SiteHelpersAssets;
use Common\Uri as CommonUri;

use Backend\Core\Engine\DataGridDB as BackendDataGridDB;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Backend\Core\Engine\Authentication;
use Backend\Modules\Tags\Engine\Model as BackendTagsModel;



/**
 * This is the edit-action, it will display a form with the item data to edit
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Edit extends ActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        $this->languages = SiteHelpersHelper::getActiveLanguages();
        //SiteHelpersAssets::addSelect2($this->header);

        $this->loadData();
        $this->loadImagesDataGrid();
        $this->loadEntriesDataGrid();
        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    private function loadImagesDataGrid()
    {

        // create dataGrid
        $this->dataGrid = new BackendDataGridDB(BackendDownloadsImagesModel::QRY_DATAGRID_BROWSE_IMAGES_FOR_PROJECT, array($this->record['id']));
        $this->dataGrid->setMassActionCheckboxes('checkbox', '[id]');

        // set drag and drop
        $this->dataGrid->enableSequenceByDragAndDrop();

        // disable paging
        $this->dataGrid->setPaging(false);

        // set colum URLs
        //$this->dataGrid->setColumnURL('preview', Model::createURLForAction('edit_image') . '&amp;id=[id]&amp;album_id=' . $this->id);

        // set colums hidden
        // $this->dataGrid->setColumnsHidden(array('category_id', 'sequence'));

        // add edit column
        $this->dataGrid->addColumn('edit', null, Language::lbl('Edit'), Model::createURLForAction('edit_image') . '&amp;id=[id]&amp;download_id=' . $this->id, Language::lbl('Edit'));

        $this->dataGrid->addColumn('delete', null, Language::lbl('Delete'), Model::createURLForAction('DeleteImage') . '&amp;id=[id]', Language::lbl('Delete'));


        $this->dataGrid->addColumn('preview', \SpoonFilter::ucfirst(Language::lbl('Preview')));
        $this->dataGrid->setColumnFunction(array('Backend\Modules\SiteHelpers\Engine\Helper', 'getPreviewHTML'), array('[filename]','Downloads','images','200x'), 'preview', true);

        // make sure the column with the handler is the first one
        $this->dataGrid->setColumnsSequence('dragAndDropHandle','checkbox','preview','filename','delete');

        // Hidden
        $this->dataGrid->setColumnsHidden(array('filename','checkbox'));

        // add a class on the handler column, so JS knows this is just a handler
        $this->dataGrid->setColumnAttributes('dragAndDropHandle', array('class' => 'dragAndDropHandle'));

        // our JS needs to know an id, so we can send the new order
        $this->dataGrid->setRowAttributes(array('id' => '[id]'));

        $this->dataGrid->setAttributes(array('data-action' => "SequenceImages"));

        // add mass action dropdown
        $ddmMassAction = new \SpoonFormDropdown('action', array('-' =>  Language::getLabel('Choose'), 'delete' => Language::getLabel('Delete')), '-');
        $ddmMassAction->setAttribute('id', 'actionDelete');
        //$this->dataGrid->setMassAction($ddmMassAction);
        //$this->frm->add($ddmMassAction);

        $this->tpl->assign('imagesDataGrid', ($this->dataGrid->getNumResults() != 0) ? $this->dataGrid->getContent() : false);
    }

    private function loadEntriesDataGrid()
    {
        // create dataGrid
        $this->entriesDataGrid = new BackendDataGridDB(BackendDownloadsEntriesModel::QRY_DATAGRID_BROWSE, array($this->record['id']));

        $this->tpl->assign('entriesDataGrid', ($this->entriesDataGrid->getNumResults() != 0) ? $this->entriesDataGrid->getContent() : false);
    }

    /**
     * Load the item data
     */
    protected function loadData()
    {
        $this->id = $this->getParameter('id', 'int', null);
        if ($this->id == null || !BackendDownloadsModel::exists($this->id)) {
            $this->redirect(
                Model::createURLForAction('Index') . '&error=non-existing'
            );
        }

        $this->record = BackendDownloadsModel::get($this->id);
    }

    /**
     * Load the form
     */
    protected function loadForm()
    {
        // create form
        $this->frm = new Form('edit');

        $this->frm->addImage('image');
        $this->frm->addHidden('id', $this->record['id']);
        $this->frm->addCheckbox('delete_image');
        $this->frm->addDate('publish_on_date', $this->record['publish_on']);
        $this->frm->addTime('publish_on_time', date('H:i', $this->record['publish_on']));

        // set hidden values
        $rbtHiddenValues[] = array('label' => Language::lbl('Hidden', $this->URL->getModule()), 'value' => 'Y');
        $rbtHiddenValues[] = array('label' => Language::lbl('Published'), 'value' => 'N');

        $this->frm->addRadiobutton('hidden', $rbtHiddenValues, $this->record['hidden']);

        $this->frm->addCheckbox('require_details', $this->record['require_details'] == 'Y');
        $this->frm->addCheckbox('download_widget', $this->record['download_widget'] == 'Y');

        // set size values
        $rbtSizeValues[] = array('label' => Language::getLabel('Small'), 'value' => 'small');
        $rbtSizeValues[] = array('label' => Language::getLabel('Medium'), 'value' => 'medium');
        $rbtSizeValues[] = array('label' => Language::getLabel('Large'), 'value' => 'large');
        $this->frm->addRadiobutton('size', $rbtSizeValues, $this->record['size']);

        $selected = SiteHelpersModel::getLinked($this->id, 'category_id', 'download_id', 'downloads_linked_catgories');
        //$this->categories = BackendDownloadsCategoryModel::getForDropdown();
         //$this->frm->addDropdown('categories', $this->categories, $selected, true, 'select select2', 'selectError select2');

         $this->categories = BackendDownloadsCategoryModel::getForMultiCheckbox();
         if(!empty($this->categories) && Authentication::isAllowedAction('AddCategory')) $this->frm->addMultiCheckbox('categories', $this->categories, $selected );

        foreach($this->languages as &$language)
        {
            $field = $this->frm->addText('name_'. $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['name']) ? $this->record['content'][$language['abbreviation']]['name'] : '', null, 'form-control title', 'form-control danger title');
            $language['name_field'] = $field->parse();

            $field = $this->frm->addEditor('description_'. $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['description']) ? $this->record['content'][$language['abbreviation']]['description'] : '');
            $language['description_field'] = $field->parse();

            $field = $this->frm->addText('tags_'. $language['abbreviation'], BackendTagsModel::getTags($this->URL->getModule(), $this->record['id'], 'string', $language['abbreviation']), null, 'form-control', 'form-control danger');
            $language['tags_field'] = $field->parse();


            $field = $this->frm->addCheckbox('seo_url_overwrite_'. $language['abbreviation'], $this->record['content'][$language['abbreviation']]['seo_url_overwrite'] == 'Y');
            $language['seo_url_overwrite_field'] = $field->parse();

            $field = $this->frm->addCheckbox('seo_description_overwrite_'. $language['abbreviation'], $this->record['content'][$language['abbreviation']]['seo_description_overwrite'] == 'Y');
            $language['seo_description_overwrite_field'] = $field->parse();

            $field = $this->frm->addCheckbox('seo_title_overwrite_'. $language['abbreviation'], $this->record['content'][$language['abbreviation']]['seo_title_overwrite'] == 'Y');
            $language['seo_title_overwrite_field'] = $field->parse();


            $field = $this->frm->addFile('file_'. $language['abbreviation']);
            $language['file_field'] = $field->parse();

            if($this->record['content'][$language['abbreviation']]['file']){
                $field = $this->frm->addCheckbox('delete_file_'. $language['abbreviation']);
                $language['delete_file_field'] = $field->parse();
            }

            $language['file'] = $this->record['content'][$language['abbreviation']]['file'];
            $language['num_downloads'] = $this->record['content'][$language['abbreviation']]['num_downloads'];

            $field = $this->frm->addText('url_'. $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['url']) ? $this->record['content'][$language['abbreviation']]['url'] : '');
            $language['url_field'] = $field->parse();

            $field = $this->frm->addText('seo_title_'. $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['seo_title']) ? $this->record['content'][$language['abbreviation']]['seo_title'] : '');
            $language['seo_title_field'] = $field->parse();

            $field = $this->frm->addText('seo_description_'. $language['abbreviation'], isset($this->record['content'][$language['abbreviation']]['seo_description']) ? $this->record['content'][$language['abbreviation']]['seo_description'] : '');
            $language['seo_description_field'] = $field->parse();

            $url = Model::getURLForBlock($this->URL->getModule(), 'Detail',  $language['abbreviation']);
            $url404 = Model::getURL(404,  $language['abbreviation']);
            $language['slug'] = isset($this->record['content'][$language['abbreviation']]['url']) ? $this->record['content'][$language['abbreviation']]['url'] : '';
            if($url404 != $url) $language['url'] = SITE_URL . $url;
        }
    }

    /**
     * Parse the page
     */
    protected function parse()
    {
        parent::parse();

        $this->tpl->assign('languages', $this->languages);
        $this->tpl->assign('draft', $this->record['status'] == 'draft');
        $this->tpl->assign('record', $this->record);
        $this->tpl->assign('imageUrl', SiteHelpersHelper::getImageUrl($this->record['image'], $this->getModule()));
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


            foreach($this->languages as $key => $language)
            {
                 $field = $this->frm->getField('name_'. $this->languages[$key]['abbreviation'])->isFilled(Language::getError('FieldIsRequired'));
                 $this->languages [$key]['name_errors'] = $this->frm->getField('name_'. $this->languages[$key]['abbreviation'])->getErrors();
            }


            if ($this->frm->isCorrect()) {

                $item['id'] = $this->id;
                $item['hidden'] = $fields['hidden']->getValue();
                $item['publish_on'] = Model::getUTCDate(null, Model::getUTCTimestamp($this->frm->getField('publish_on_date'), $this->frm->getField('publish_on_time')));
                $item['status'] = $status;
                $item['size'] = $fields['size']->getValue();
                $item['require_details'] = $fields['require_details']->isChecked() ? 'Y' : 'N';
                $item['download_widget'] = $fields['download_widget']->isChecked() ? 'Y' : 'N';

                if(!empty($this->categories) && Authentication::isAllowedAction('AddCategory'))
                {
                    SiteHelpersModel::insertLinked(
                        $this->frm->getField('categories')->getValue(),
                        'category_id',
                        $item['id'],
                        'download_id',
                        'downloads_linked_catgories'
                    );
                }

                $imagePath = SiteHelpersHelper::generateFolders($this->getModule());

                if($fields['delete_image']->isChecked()){
                    $item['image'] = NULL;
                    Model::deleteThumbnails(FRONTEND_FILES_PATH . '/' . $this->getModule() . '/image',  $this->record['image']);
                }

                // image provided?
                if ($fields['image']->isFilled()) {
                    // replace old image
                    if($this->record['image'])
                    {
                      $item['image'] = NULL;
                      Model::deleteThumbnails(FRONTEND_FILES_PATH . '/' . $this->getModule() . '/image',  $this->record['image']);
                    }

                    // build the image name
                    $item['image'] = uniqid() . '.' . $fields['image']->getExtension();

                    // upload the image & generate thumbnails
                    $fields['image']->generateThumbnails($imagePath, $item['image']);
                }


                $content = array();

                foreach($this->languages as $language)
                {
                    $specific['extra_id'] = $this->record['content'][$language['abbreviation']]['extra_id'];
                    $specific['download_id'] = $item['id'];
                    $specific['language'] = $language['abbreviation'];
                    $specific['name'] = $this->frm->getField('name_'. $language['abbreviation'])->getValue();
                    $specific['description'] = $this->frm->getField('description_'. $language['abbreviation'])->getValue() ? $this->frm->getField('description_'. $language['abbreviation'])->getValue() : null;
                    $specific['download_widget_extra_id'] = $this->record['content'][$language['abbreviation']]['download_widget_extra_id'];

                    $specific['seo_url_overwrite'] = $this->frm->getField('seo_url_overwrite_'. $language['abbreviation'])->isChecked() ? 'Y' : 'N';
                    $specific['seo_description_overwrite'] = $this->frm->getField('seo_description_overwrite_'. $language['abbreviation'])->isChecked() ? 'Y' : 'N';
                    $specific['seo_title_overwrite'] = $this->frm->getField('seo_title_overwrite_'. $language['abbreviation'])->isChecked() ? 'Y' : 'N';

                    $specific['url'] =  BackendDownloadsModel::getURL(CommonUri::getUrl($specific['name']), $language['abbreviation'], $this->record['id']);
                    if($specific['seo_url_overwrite'] == 'Y') $specific['url'] = BackendDownloadsModel::getURL(CommonUri::getUrl( $this->frm->getField('url_'. $language['abbreviation'])->getValue()), $language['abbreviation']);

                    $specific['seo_description'] = $specific['name'];
                    if($specific['seo_description_overwrite'] == 'Y') $specific['seo_description'] = $this->frm->getField('seo_description_'. $language['abbreviation'])->getValue() ? $this->frm->getField('seo_description_'. $language['abbreviation'])->getValue() : null;

                    $specific['seo_title'] = $specific['name'];
                    if($specific['seo_title_overwrite'] == 'Y') $specific['seo_title'] = $this->frm->getField('seo_title_'. $language['abbreviation'])->getValue() ? $this->frm->getField('seo_title_'. $language['abbreviation'])->getValue() : null;

                        $fs = new Filesystem();

                    // delete file
                    if($this->record['content'][$language['abbreviation']]['file'])
                    {
                        if($this->frm->getField('delete_file_'. $language['abbreviation'])->isChecked())
                        {
                            $specific['file'] = '';

                            $fs->remove(FRONTEND_FILES_PATH . '/Downloads/file/' . $this->record['content'][$language['abbreviation']]['file']);
                        }
                    }

                    // file
                    if($this->frm->getField('file_'. $language['abbreviation'])->isFilled())
                    {
                        // remove old file if filled

                        if($this->record['content'][$language['abbreviation']]['file']){
                            $specific['file'] = '';

                            $fs->remove(FRONTEND_FILES_PATH . '/Downloads/file/' . $this->record['content'][$language['abbreviation']]['file']);
                        }

                        $specific['file']  =  $specific['url']
                        . '-' . $specific['language']
                        . '.' . $this->frm->getField('file_'. $language['abbreviation'])->getExtension();

                        $specific['file_extensions'] = $this->frm->getField('file_'. $language['abbreviation'])->getExtension();
                        $specific['file_size'] = $this->frm->getField('file_'. $language['abbreviation'])->getFileSize('mb');

                        $this->frm->getField('file_'. $language['abbreviation'])->moveFile(FRONTEND_FILES_PATH . '/Downloads/file/' . $specific['file'] ,'0775');
                    }


                    $content[$language['abbreviation']] = $specific;

                    // save tags
                    BackendTagsModel::saveTags($item['id'], $this->frm->getField('tags_'. $language['abbreviation'])->getValue(), $this->URL->getModule(), $language['abbreviation']);

                     BackendSearchModel::saveIndex(
                        $this->getModule(), $item['id'],
                        array('name' => $specific['name'], 'description' => $specific['description']),
                        $language['abbreviation']
                    );
                }

                BackendDownloadsModel::update($item);
                BackendDownloadsModel::updateContent($content, $item['id'], $item);

                Model::triggerEvent(
                    $this->getModule(), 'after_edit', $item
                );
                $this->redirect(
                    Model::createURLForAction('Edit') . '&report=edited&id=' . $item['id']
                );
            }
        }
    }
}
