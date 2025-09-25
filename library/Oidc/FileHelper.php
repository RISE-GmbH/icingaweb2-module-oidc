<?php

namespace Icinga\Module\Oidc;

use Icinga\Application\Logger;

class FileHelper
{
    protected $path = '';

    public function __construct($path)
    {
        $this->path=$path;
    }

    public function fetchFileList(){
        $directory = $this->path;
        if(! file_exists($directory)){
            return [];
        }
        $files  = scandir($directory);

        $files = array_diff($files, array('.', '..'));

        $files = array_filter($files, function($file) use ($directory) {
            return is_file($directory .DIRECTORY_SEPARATOR.$file) || is_link($directory .DIRECTORY_SEPARATOR.$file);
        });

        return $files;
    }
    public function filelistAsSelect(){
        $result =[];
        $files= $this->fetchFileList();
        foreach ($files as $file){
            $result[$file]=$file;
        }
        return $result;
    }

    public function getFile($fileToGet){
        $allFiles = $this->fetchFileList();

        // if the file is in the directory we can be sure that the access ok
        if (in_array($fileToGet, $allFiles)) {
            $filePath = $this->path.DIRECTORY_SEPARATOR.$fileToGet;

            if(is_link($filePath)){
                $filePath = readlink($filePath);
            }else{
                $filePath = realpath($filePath);
            }
            return ['realPath'=>$filePath, 'size'=>filesize($filePath), 'name'=>$fileToGet];
        }
        return false;
    }
}
