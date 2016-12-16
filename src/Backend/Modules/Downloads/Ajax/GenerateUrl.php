<?php


namespace Backend\Modules\Downloads\Ajax;

use Backend\Core\Engine\Base\AjaxAction;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;

use Common\Uri;
use SpoonFilter;

use Backend\Core\Engine\Exception;
use Backend\Core\Engine\Model;

/**
 * This action will generate a valid url based upon the submitted url.
 */
class GenerateUrl extends AjaxAction
{
    /**
     * Execute the action
     */
    public function execute()
    {
        // call parent, this will probably add some general CSS/JS or other required files
        parent::execute();

        // get parameters
        $url = \SpoonFilter::getPostValue('url', null, '', 'string');
        $id = \SpoonFilter::getPostValue('id', null, '', 'int');
        $class = \SpoonFilter::getPostValue('className', null, '', 'string');
        $method = \SpoonFilter::getPostValue('methodName', null, '', 'string');
        $language = strtolower(\SpoonFilter::getPostValue('language', null, 'en', 'string'));

        // check if the class is a service
        if (Model::getContainer()->has($class)) {
            $class = Model::getContainer()->get($class);
        }

        // validate (check if the function exists)
        if (!is_callable([$class, $method])) {
            throw new Exception('The callback-method doesn\'t exist.');
        }

        // when using ->getValue() in SpoonFormText fields the function is using htmlentities(),
        // so we must decode it again first!
        $url = SpoonFilter::htmlentitiesDecode($url);

        $actualParameters = [];
        // build parameters for use in the callback
        $actualParameters[] = Uri::getUrl($url);
        $actualParameters[] = $language;
        $actualParameters[] = $id;

        // get the real url
        $url =  call_user_func_array([$class, $method], $actualParameters);


        // output
        $this->output(self::OK, $url);
    }
}
