<?php

namespace Frontend\Modules\Downloads\Actions;

use Frontend\Core\Engine\Base\Block;
use Frontend\Core\Engine\Model;
use Frontend\Core\Engine\Navigation;
use Frontend\Modules\Downloads\Engine\Model as FrontendDownloadsModel;
use Frontend\Modules\Downloads\Engine\Categories as FrontendDownloadsCategoriesModel;
use Frontend\Core\Language\Language;
use Frontend\Core\Engine\Form as FrontendForm;
use Frontend\Modules\Downloads\Engine\Entries as FrontendDownloadsEntriesModel;
use Frontend\Modules\SiteHelpers\Engine\Email as FrontendnSiteHelpersEmail;
use Frontend\Modules\SiteHelpers\Engine\Template as FrontendnSiteHelpersTemplate;
use Common\Uri as CommonUri;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This is the index-action (default), it will display the overview of Downloads posts
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Detail extends Block
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
        $this->tpl->assignGlobal('hideContentTitle', true);
        $this->loadTemplate();
        $this->getData();
        $this->loadForm();
        $this->validateForm();
        $this->parse();
    }

    private function loadForm()
    {
        $this->frm = new FrontendForm('details');
        $this->frm->setAction($this->frm->getAction() . '#details');

        // create elements
        $this->frm->addText('first_name')->setAttributes(array('required' => null));
        $this->frm->addText('last_name')->setAttributes(array('required' => null));
        $this->frm->addText('email')->setAttributes(array('required' => null, 'type' => 'email'));
    }

    /**
     * Validate the form.
     */
    private function validateForm()
    {

        // is the form submitted
        if ($this->frm->isSubmitted()) {
            // cleanup the submitted fields, ignore fields that were added by hackers
            $this->frm->cleanupFields();

            // validate required fields
            $this->frm->getField('first_name')->isFilled(Language::err('FieldIsRequired'));
            $this->frm->getField('last_name')->isFilled(Language::err('FieldIsRequired'));
            if ($this->frm->getField('email')->isFilled(Language::err('FieldIsRequired'))) {
                $this->frm->getField('email')->isEmail(Language::err('EmailIsInvalid'));
            }

            // no errors?
            if ($this->frm->isCorrect()) {

                // build array
                $insert['first_name'] = $this->frm->getField('first_name')->getValue();
                $insert['last_name'] = $this->frm->getField('last_name')->getValue();
                $insert['email'] = $this->frm->getField('email')->getValue();
                $insert['download_id'] = $this->record['id'];

                // file provided?
                FrontendDownloadsEntriesModel::insert($insert);

                $session = new Session();
                $session->set('download-' . $this->record['id'], true);

                // redirect
                $this->redirect( $this->record['full_url'] . '/' . Language::getAction('Success'));

            }
        }
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

        // load revision
        if ($this->URL->getParameter('draft', 'bool')) {
            // get data
            $this->record = FrontendDownloadsModel::getDraft($parameter);

            // add no-index, so the draft won't get accidentally indexed
            $this->header->addMetaData(array('name' => 'robots', 'content' => 'noindex, nofollow'), true);
        } else {
            // get by URL
             $this->record = FrontendDownloadsModel::get($parameter);
        }

        if (empty($this->record)) {
            $this->redirect(Navigation::getURL(404));
        }

        // Redirect not on draft and detailsor not required
        if (!$this->URL->getParameter('draft', 'bool') && $this->record['require_details'] == 'N') $this->redirect(Navigation::getURLForBlock('Downloads', 'Download') . '/' . $this->record['url']);

        // get status
        $this->status = $this->URL->getParameter(2);
        if ($this->status == Language::getAction('Success')) {
            $this->status = 'success';

            // redirect for download

            // assign the header so we can download the file
			$this->header->addMetaData(array(
				'http-equiv' => 'refresh',
				'content' => '2;url=' . Navigation::getURLForBlock('Downloads','Download') . '/' . $this->record['url']
			));

        }
    }

    /**
     * Parse the page
     */
    protected function parse()
    {
        if($this->get('fork.settings')->get('Downloads', 'use_image_as_og_image') && $this->record['image'])
            $this->header->addOpenGraphImage(FRONTEND_FILES_URL . '/Downloads/image/1200x630/' . $this->record['image']);

        // build Facebook  OpenGraph data
        $this->header->addOpenGraphData('title', $this->record['name'], true);
        $this->header->addOpenGraphData(
            'url',
            SITE_URL . $this->record['full_url'],
            true
        );
        $this->header->addOpenGraphData(
            'site_name',
            $this->get('fork.settings')->get('Core', 'site_title_' . FRONTEND_LANGUAGE, SITE_DEFAULT_TITLE),
            true
        );
        $this->header->addOpenGraphData('description', $this->record['seo_description'], true);

        // add into breadcrumb
        $this->breadcrumb->addElement($this->record['name']);
        // set meta
        $this->header->setPageTitle($this->record['seo_title'], ($this->record['seo_title_overwrite'] == 'Y'));
        $this->header->addMetaDescription(
            $this->record['seo_description'],
            ($this->record['seo_description_overwrite'] == 'Y')
        );

        $navigation = FrontendDownloadsModel::getNavigation($this->record['id']);
        $this->tpl->assign('navigation', $navigation);


        // assign item
        $this->tpl->assign('item', $this->record);

        // parse the form
        $session = new Session();
        $hasSession = $session->get('download-' . $this->record['id']);

        if (empty($this->status) && $this->record['require_details'] == 'Y' && !$hasSession ) {
            $this->frm->parse($this->tpl);
        }

        // parse the form status
        if (!empty($this->status)) {
            $this->tpl->assign($this->status, true);
        }
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
