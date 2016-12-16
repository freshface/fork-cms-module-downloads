<?php

namespace Frontend\Modules\Downloads\Widgets;

use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Modules\Downloads\Engine\Model as FrontendDownloadsModel;

class Category extends FrontendBaseWidget
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
        if (isset($this->data['id'])) {
            $filter['categories'][] = $this->data['id'];
            $this->tpl->assign('widgetDownloadsCategory', FrontendDownloadsModel::getAll(3, 0, $filter));
        }
    }
}
