<?php

namespace Backend\Modules\Downloads\Ajax;

use Backend\Core\Engine\Base\AjaxAction;

use Common\Uri as CommonUri;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Language as BL;
use Backend\Modules\Downloads\Engine\Model as BackendDownloadsModel;
use Backend\Modules\Downloads\Engine\Images as BackendDownloadsImagesModel;

use Backend\Modules\SiteHelpers\Engine\Helper as SiteHelpersHelper;

/**
 * Uploads files to the server
 *
 * @author Frederik Heyninck <frederikheyninck@gmail.com>
 */
class UploadImage extends AjaxAction
{
    public function execute()
    {
        // call parent, this will probably add some general CSS/JS or other required files
        parent::execute();

        $verify_token =  md5(\SpoonFilter::getPostValue('timestamp', null, '', 'string'));
        $token = \SpoonFilter::getPostValue('token', null, '', 'string');
        $this->id = \SpoonFilter::getPostValue('id', null, '', 'int');

        $this->languages = SiteHelpersHelper::getActiveLanguages();

        if (!empty($_FILES)) {
            if ($token == $verify_token) {
                ini_set('memory_limit', -1);

                // Upload
                self::upload($_FILES);

                ini_restore('memory_limit');

                $this->output(self::OK, null, '1');
            } else {
                $this->output(self::ERROR, null, 'invalid token');
            }
        } else {
            $this->output(self::ERROR, null, 'no files selected');
        }
    }


    /**
     * Validate the image
     *
     * @param string $field The name of the field
     * @param int $set_idThe id of the set
     */
    private function upload($file)
    {
        $file_data = $file['Filedata'];

        if ($file_data) {
            $file_parts = pathinfo($file_data['name']);
            $temp_file   = $file_data['tmp_name'];

            $extension = $file_parts['extension'];
            $original_filename = $file_parts['filename'];

            $allowed_types = null; // Allowed file types

            if (filesize($temp_file) > 0) {
                // Generate a unique filename
                $filename = (CommonUri::getUrl($file_parts['filename']) . uniqid() . '.' . strtolower($extension));

                // path to folder
                $files_path = FRONTEND_FILES_PATH . '/' . $this->getModule() . '/images/source';

                $fs = new Filesystem();
                $fs->mkdir($files_path, 0775);

                // Move the file
                move_uploaded_file($temp_file, $files_path . '/' . $filename);
                chmod($files_path . '/' . $filename, 0775);

                $insert['filename'] = $filename;
                $insert['download_id'] = $this->id;
                $insert['data'] = serialize(array());

                $insert['sequence'] = BackendDownloadsImagesModel::getMaximumSequence($this->id) + 1;
                $insert['hidden'] = 'N';

                //BackendDownloadsHelper::generateThumbnail($filename, $files_path, $preview_files_path);

                list($width, $height) = getimagesize($files_path . '/' . $filename);

                $data['portrait'] = ($width > $height) ? false : true;
                $insert['data'] = serialize($data);


                $insert['id'] = BackendDownloadsImagesModel::insert($insert);

                $content = array();

                foreach ($this->languages as $language) {
                    $specific['image_id'] = $insert['id'];

                    $specific['language'] = $language['abbreviation'];
                    $specific['name'] = '';
                    $specific['url'] =  '';
                    $specific['description'] = '';
                    $content[$language['abbreviation']] = $specific;
                }

                 // insert it
               BackendDownloadsImagesModel::insertContent($content);

                $imagePath = SiteHelpersHelper::generateFolders($this->getModule(), 'images', array('1200x630', '600x315'));

                BackendModel::generateThumbnails($imagePath, $files_path . '/' . $filename);
            } else {
                echo 'Invalid file type.';
                exit;
                //$this->output(self::ERROR, null, 'Invalid file type.');
            }
        }
    }
}
