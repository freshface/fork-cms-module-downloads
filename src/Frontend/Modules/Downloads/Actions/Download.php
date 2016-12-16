<?php

namespace Frontend\Modules\Downloads\Actions;

use Frontend\Core\Engine\Base\Block;
use Frontend\Core\Engine\Model;
use Frontend\Core\Engine\Navigation;
use Frontend\Modules\Downloads\Engine\Model as FrontendDownloadsModel;
use Frontend\Modules\Downloads\Engine\Categories as FrontendDownloadsCategoriesModel;
use Frontend\Core\Language\Language;
use Frontend\Core\Engine\Form as FrontendForm;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This is the index-action (default), it will display the overview of Downloads posts
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Download extends Block
{
    /**
     * The record
     *
     * @var    array
     */
    private $record;

    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();
        $this->loadTemplate();
        $this->getData();
        $this->parse();
    }


    /**
     * Get the data
     */
    private function getData()
    {
        $parameter = $this->URL->getParameter(1);

        if (empty($parameter)) {
            $this->redirect(Navigation::getURL(404));
        }

        // get by URL
         $this->record = FrontendDownloadsModel::get($parameter);

        if (empty($this->record)) {
            $this->redirect(Navigation::getURL(404));
        }

        $file = FRONTEND_FILES_PATH . '/Downloads/file/' . @$this->record['file'];
        $fs = new Filesystem();


        $session = new Session();
        $hasSession = $session->get('download-' . $this->record['id']);


        if ($fs->exists($file) && !empty($this->record['file']) && ($this->record['require_details'] == 'N' || $hasSession)) {
            $content[FRONTEND_LANGUAGE] = array('num_downloads' => (int) $this->record['num_downloads'] + 1);
            FrontendDownloadsModel::updateContent($content, $this->record['id']);
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $this->record['file'] . "\"");
            readfile($file);
            exit;
        } else {
            $this->redirect(Navigation::getURL(404));
        }
    }

    /**
     * Parse the page
     */
    protected function parse()
    {
    }

    /**
     * @return mixed
     */
    private function getLastParameter()
    {
        $numberOfParameters = count($this->URL->getParameters());
        return $this->URL->getParameter($numberOfParameters - 1);
    }
}
