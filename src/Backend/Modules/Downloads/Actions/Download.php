<?php

namespace Backend\Modules\Downloads\Actions;

use Backend\Core\Engine\Base\ActionEdit;
use Backend\Core\Engine\Form;
use Backend\Core\Language\Language;
use Backend\Core\Engine\Model;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * This is the edit-action, it will display a form with the item data to edit
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Download extends ActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();
        $this->loadData();

        $this->parse();
        $this->display();
    }


    /**
     * Load the item data
     */
    protected function loadData()
    {
        $this->id = $this->getParameter('id', 'int', null);
        $this->language = $this->getParameter('language', 'string', 'en');
        if ($this->id == null || !BackendDownloadsModel::exists($this->id)) {
            $this->redirect(
                Model::createURLForAction('Index') . '&error=non-existing'
            );
        }

        $this->record = BackendDownloadsModel::get($this->id);
    }

    /**
     * Parse the page
     */
    protected function parse()
    {
        parent::parse();
        $file = FRONTEND_FILES_PATH . '/Downloads/file/' . $this->record['content'][$this->language]['file'];
        $fs = new Filesystem();

        if($fs->exists($file)){

            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $this->record['content'][$this->language]['file'] . "\"");
            readfile($file);
            exit;

        } else {
            $this->redirect(
                Model::createURLForAction('Edit') . '&report=error&id=' . $this->id
            );
        }



    }

}
