<?php

namespace Backend\Modules\Downloads\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Language\Language as BL;

/**
 * This is the settings-action, it will display a form to set general Downloads settings
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 * @author Dave Lens <dave.lens@netlash.com>
 * @author Jelmer Snoeck <jelmer@siphoc.com>
 */
class Settings extends BackendBaseActionEdit
{
    /**
     * Is the user a god user?
     *
     * @var bool
     */
    protected $isGod = false;

    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();
        $this->loadForm();
        $this->validateForm();
        $this->parse();
        $this->display();
    }

    /**
     * Loads the settings form
     */
    private function loadForm()
    {
        $this->isGod = BackendAuthentication::getUser()->isGod();

        $this->frm = new BackendForm('settings');

        // add fields for pagination
        $this->frm->addDropdown(
            'overview_number_of_items',
            array_combine(range(1, 30), range(1, 30)),
            $this->get('fork.settings')->get($this->URL->getModule(), 'overview_number_of_items', 10)
        );

        $this->frm->addCheckbox(
           'allow_subcategories',
           $this->get('fork.settings')->get($this->getModule(), 'allow_subcategories', false)
       );

        $this->frm->addCheckbox(
          'use_image_as_og_image',
          $this->get('fork.settings')->get($this->getModule(), 'use_image_as_og_image', true)
      );

        $this->frm->addCheckbox(
         'make_widget_per_category',
         $this->get('fork.settings')->get($this->getModule(), 'make_widget_per_category', true)
     );


        $this->frm->addCheckbox(
        'allow_tags',
        $this->get('fork.settings')->get($this->getModule(), 'allow_tags', false)
    );
    }

    /**
     * Parse the form
     */
    protected function parse()
    {
        parent::parse();

        // parse additional variables
        $this->tpl->assign('isGod', $this->isGod);
    }

    /**
     * Validates the settings form
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            if ($this->frm->isCorrect()) {
                // set our settings
                $this->get('fork.settings')->set($this->URL->getModule(), 'overview_number_of_items', (int) $this->frm->getField('overview_number_of_items')->getValue());

                $this->get('fork.settings')->set(
                    $this->getModule(),
                    'allow_subcategories',
                    (bool) $this->frm->getField('allow_subcategories')->getValue()
                );

                $this->get('fork.settings')->set(
                    $this->getModule(),
                    'use_image_as_og_image',
                    (bool) $this->frm->getField('use_image_as_og_image')->getValue()
                );

                $this->get('fork.settings')->set(
                    $this->getModule(),
                    'make_widget_per_category',
                    (bool) $this->frm->getField('make_widget_per_category')->getValue()
                );



                $this->get('fork.settings')->set(
                    $this->getModule(),
                    'allow_tags',
                    (bool) $this->frm->getField('allow_tags')->getValue()
                );


                // trigger event
                BackendModel::triggerEvent($this->getModule(), 'after_saved_settings');

                // redirect to the settings page
                $this->redirect(BackendModel::createURLForAction('Settings') . '&report=saved');
            }
        }
    }
}
