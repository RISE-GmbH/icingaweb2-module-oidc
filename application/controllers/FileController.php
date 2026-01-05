<?php

namespace Icinga\Module\Oidc\Controllers;

use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Exception\Http\HttpException;

use Icinga\Module\Oidc\FileHelper;
use Icinga\Module\Oidc\FilesTable;
use Icinga\Module\Oidc\Forms\FileUploadForm;

use Icinga\Web\Notification;
use Icinga\Web\Url;

use ipl\Html\Html;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\ButtonLink;

class FileController extends CompatController
{
    protected $path = "";
    public function checkAndCreateFolder($folder)
    {
        if (!file_exists($folder)) {
            try {
                mkdir($folder, 0755, true);
            } catch (\Throwable $e) {
                Notification::error($folder . " could not be created, create manually and / or change permissions");
                Logger::error($folder . " could not be created, create manually and / or change permissions");
                return false;
            }

        }

        if (!is_writable($folder)) {
            Logger::error($folder . " is not writeable, please fix manually");
            Notification::error($folder . " is not writeable, please fix manually");
            return false;
        }
        return true;
    }

    public function init()
    {
        $this->path = Module::get('oidc')->getConfigDir().DIRECTORY_SEPARATOR."files";
        $this->checkAndCreateFolder($this->path);
    }

    public function deleteAction()
    {
        $this->assertPermission('oidc/file/delete');

        $this->addTitleTab($this->translate('Delete'));

        $fileToGet = $this->params->shift('name');
        $fileHelper = new FileHelper($this->path);
        $file = $fileHelper->getFile($fileToGet);
        if ($file !== false) {
            unlink($file['realPath']);
            $this->redirectNow('oidc/file');
            return;
        }
        throw new HttpException(401,"Don't do this again...");

    }
    public function uploadAction()
    {
        $this->assertPermission('oidc/file/upload');

        $this->addTitleTab($this->translate('Upload'));
        $title = $this->translate('Upload a file');
        $this->view->headline= Html::tag('h1', null, $title);
        $form = (new FileUploadForm())->setUploadPath($this->path);
        $form->handleRequest();

        $this->view->form= $form;
    }

    public function viewAction()
    {
        $this->assertPermission('oidc/file/view');

        $this->addTitleTab($this->translate('View'));

        $fileToGet = $this->params->shift('name');
        $fileHelper = new FileHelper($this->path);
        $file = $fileHelper->getFile($fileToGet);
        $h1 = Html::tag('h1',null,"File: ".$fileToGet);
        $this->addContent($h1);

        if ($file !== false) {
            $fileContent = file_get_contents($file['realPath']);
            $mimeType = mime_content_type($file['realPath']);
            if(strpos($mimeType,'image') !== false){
                $fileRenderer= Html::tag('img',['src'=>'data:'.$mimeType.";base64, ".base64_encode($fileContent)]);
            }else{
                $fileRenderer= Html::tag('pre',null,$fileContent);
            }
            $this->addContent($fileRenderer);

            return;
        }
        throw new HttpException(401,"Don't do this again...");

    }


    public function indexAction()
    {
        $this->assertPermission('oidc/file');
        $this->addTitleTab($this->translate('Files'));

        if ($this->hasPermission('oidc/file/upload')) {
            $this->addControl(
                (new ButtonLink($this->translate('Upload'), \ipl\Web\Url::fromPath('oidc/file/upload'), 'plus'))
                    ->openInModal()
            );
        }
        $fileHelper = new FileHelper($this->path);
        $files = $fileHelper->fetchFileList();


        $data =[];
        foreach ($files as $file) {
            $file = $fileHelper->getFile($file);
            $item = ['name'=>$file['name'], 'size'=>$file['size'], 'downloadname'=>$file['name']];
            $data[]= (object) $item;
        }

        $this->addContent((new FilesTable())->setData($data));


    }


    public function downloadAction()
    {
        $this->assertPermission('oidc/file/download');
        $fileToGet = $this->params->shift('name');
        $fileHelper = new FileHelper($this->path);
        $file = $fileHelper->getFile($fileToGet);
        if ($file !== false) {
            ob_get_clean();
            header('Content-Description: File Transfer');
            header("Content-type: application/octet-stream");
            header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $file['size']);
            ob_clean();
            flush();
            readfile($file['realPath']);
            exit;
        }


    }


    public function checkFolder($folder)
    {
        if (!file_exists($folder)) {
            Notification::error(t($folder . " is not readable, please fix manually"));
            return false;
        }

        return true;
    }



}