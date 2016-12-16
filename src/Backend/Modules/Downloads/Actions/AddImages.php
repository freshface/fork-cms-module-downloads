<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\SiteHelpers\Engine\Assets as SiteHelpersAssets;

/**
 * Add images upload multiple action
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 * @author Tommy Van de Velde <tommy@figure8.be>
 */
class AddImages extends BackendBaseActionAdd
{
    private $filledImagedCount = 0;

    /**
     * Execute the action
     */
    public function execute()
    {
        // get parameters
        $this->id = $this->getParameter('id', 'int');

        // does the item exists
        if($this->id !== null && BackendDownloadsModel::exists($this->id))
        {
            // call parent, this will probably add some general CSS/JS or other required files
            parent::execute();

            SiteHelpersAssets::addUploadifive($this->header);

            // get all data for the item we want to edit
            $this->getData();

            // load the form
            $this->loadForm();

            // parse
            $this->parse();

            // display the page
            $this->display();
        }
        // no item found, throw an exception, because somebody is fucking with our URL
        else $this->redirect(BackendModel::createURLForAction('index') . '&error=non-existing');
    }

    /**
     * Get the data
     */
    private function getData()
    {
        // get the record
        $this->record = (array) BackendDownloadsModel::get($this->id);

        // no item found, throw an exceptions, because somebody is fucking with our URL
        if(empty($this->record)) $this->redirect(BackendModel::createURLForAction('Index') . '&error=non-existing');

        $this->tpl->assign('record', $this->record);
        
        $timestamp = time();

        $this->header->addJSData('uploadifive','upload_timestamp', $timestamp);
        $this->header->addJSData('uploadifive','upload_token', md5($timestamp));
        $this->header->addJSData('uploadifive','id', $this->id);
        $this->header->addJSData('uploadifive','upload_uploaded_success_url', BackendModel::createURLForAction('Edit') . '&id=' . $this->id . '#tabImages');
        $this->header->addJSData('uploadifive','upload_uploaded_fallback_url', ''); // not supported page

        $this->header->addJSData('uploadifive','add_files_url', BackendModel::createURLForAction('AddFiles'));
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        // create form
        $this->frm = new BackendForm('add');
        
    }

    /**
     * Parse the form
     */
    protected function parse()
    {
        // call parent
        parent::parse();
    }
}
