<?php

namespace Backend\Modules\Downloads\Installer;

use Backend\Core\Installer\ModuleInstaller;

/**
 * Installer for the Downloads module
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Installer extends ModuleInstaller
{
    public function install()
    {
        // import the sql
        $this->importSQL(dirname(__FILE__) . '/Data/install.sql');

        // install the module in the database
        $this->addModule('Downloads');

        // install the locale, this is set here beceause we need the module for this
        $this->importLocale(dirname(__FILE__) . '/Data/locale.xml');

        $this->setModuleRights(1, 'Downloads');

        $this->setActionRights(1, 'Downloads', 'Add');
        $this->setActionRights(1, 'Downloads', 'AddCategory');
        //$this->setActionRights(1, 'Downloads', 'AddImages');
        $this->setActionRights(1, 'Downloads', 'Categories');
        $this->setActionRights(1, 'Downloads', 'Delete');
        $this->setActionRights(1, 'Downloads', 'DeleteCategory');
        $this->setActionRights(1, 'Downloads', 'DeleteImage');
        $this->setActionRights(1, 'Downloads', 'Edit');
        $this->setActionRights(1, 'Downloads', 'EditCategory');
        $this->setActionRights(1, 'Downloads', 'Index');

        $this->setActionRights(1, 'Downloads', 'Sequence');
        $this->setActionRights(1, 'Downloads', 'SequenceCategories');
        $this->setActionRights(1, 'Downloads', 'SequenceImages');
        $this->setActionRights(1, 'Downloads', 'UploadImages');
        $this->setActionRights(1, 'Downloads', 'EditImage');
        $this->setActionRights(1, 'Downloads', 'GetAllTags');

        $this->setActionRights(1, 'Downloads', 'Settings');
        $this->setActionRights(1, 'Downloads', 'GenerateUrl');
        $this->setActionRights(1, 'Downloads', 'UploadImage');
        $this->setActionRights(1, 'Downloads', 'ExportEntries');
        $this->setActionRights(1, 'Downloads', 'Download');

        $this->makeSearchable('Downloads');

        // add extra's
        $subnameID = $this->insertExtra('Downloads', 'block', 'Downloads', null, null, 'N', 1000);
        $this->insertExtra('Downloads', 'block', 'DownloadsByCategory', 'ByCategory', null, 'N', 1000);
        $this->insertExtra('Downloads', 'block', 'DownloadDetail', 'Detail', null, 'N', 1001);
        $this->insertExtra('Downloads', 'block', 'Download', 'Download', null, 'N', 1001);
        $this->insertExtra('Downloads', 'widget', 'Recent', 'RecentDownloads', null, 'N', 1001);

        $navigationModulesId = $this->setNavigation(null, 'Modules');
        $navigationModulesId = $this->setNavigation($navigationModulesId, 'Downloads');
        $this->setNavigation($navigationModulesId, 'Downloads', 'downloads/index', array('downloads/add','downloads/edit', 'downloads/index', 'downloads/add_images', 'downloads/edit_image'), 1);
        $this->setNavigation($navigationModulesId, 'Categories', 'downloads/categories', array('downloads/add_category','downloads/edit_category', 'downloads/categories'), 2);

         // settings navigation
        $navigationSettingsId = $this->setNavigation(null, 'Settings');
        $navigationModulesId = $this->setNavigation($navigationSettingsId, 'Modules');
        $this->setNavigation($navigationModulesId, 'Downloads', 'downloads/settings');
    }
}
