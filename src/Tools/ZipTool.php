<?php

namespace Exhaust\Tools;

use \PhpZip;

class ZipTool
{
    /**
     * Creates a zip file based on one or more files using Ne-Lexa/php-zip library
     *
     * @see https://github.com/Ne-Lexa/php-zip
     *
     * + Requires Zip PHP extention, you can check it out by running the following command: 'php -m'
     *
     * @param array $files - array containing the files to be zipped
     * @param string $outputName - the name of the zip file to be generated
     * @return string|bool the path to the zip file to be generated
     */
    public static function fileToZip(array $files, string $outputName): string|bool
    {
        $zipFile = new PhpZip\ZipFile();
        $path_to_save_dir = $_SERVER['DOCUMENT_ROOT'] . '/temp/';

        try{
            foreach($files as $file){
                $zipFile->addFile($file);
            }

            $zipFile
            ->saveAsFile($path_to_save_dir.$outputName)
            ->close();

            return $path_to_save_dir.$outputName;

        }catch(PhpZip\Exception\ZipException $e){
            error_log("## PhpZip error " . $e->getMessage());
            return false;
        }finally{
            $zipFile->close();
            return false;
        }
    }
}