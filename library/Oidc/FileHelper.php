<?php

namespace Icinga\Module\Oidc;

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
            return is_file($directory .DIRECTORY_SEPARATOR.$file);
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
        $filePath = $this->path.DIRECTORY_SEPARATOR.$fileToGet;
        if (file_exists($filePath)) {
            return ['realPath'=>$filePath, 'size'=>filesize($filePath), 'name'=>$fileToGet];
        }
        return false;
    }
}
