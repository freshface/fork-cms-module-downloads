<?php

namespace Frontend\Modules\Downloads\Widgets;


use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Modules\Downloads\Engine\Model as FrontendDownloadsModel;
use Frontend\Modules\Downloads\Engine\Categories as FrontendDownloadsCategoriesModel;

class Categories extends FrontendBaseWidget
{
    /**
     * Execute the extra
     */
    public function execute()
    {
        parent::execute();
        $this->loadTemplate();
        $this->parse();
    }

    /**
     * Parse
     */
    private function parse()
    {
        $this->tpl->assign('widgetDownloadsCategories', FrontendDownloadsCategoriesModel::getAll(array('parent_id' => 0)));
    }
}
