<?php

namespace Frontend\Modules\Downloads\Widgets;

use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Modules\Downloads\Engine\Model as FrontendDownloadsModel;

class Recent extends FrontendBaseWidget
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
        $this->tpl->assign('widgetDownloadsRecent', FrontendDownloadsModel::getAll($this->get('fork.settings')->get('Downloads', 'overview_num_items_recent', 3)));
    }
}
