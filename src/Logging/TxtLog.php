<?php

declare(strict_types=1);

namespace Exhaust\Logging;

/**
 * class that logs content to a txt file
 */
final class TxtLog{

    private static $separator = "\r\r##################################################\r\r" . PHP_EOL;
    private static $now = "\r\r" . date('Y-m-d H:i:s') . "\r\r" . PHP_EOL;

    /**
     * opens a txt log file and writes content into it
     *
     * @param string $content
     * @param mixed $fileName
     * @return bool
     */
    public static function log(string $content, ?string $fileName = null): bool
    {
        if(is_null($fileName)){
            $fileName = '/var/www/html/storage/logs/'.date("Y-m-d") .".txt";
        }else{
            $fileName = '/var/www/html/storage/logs/'.$fileName .".txt";
        }
        if(self::checkIfFileExists($fileName)){
            return self::writeToFile($fileName, $content);
        }
        return false;
    }

    /**
     * Checks if a file exists by using the filename with path to check
     * @param string $file
     * @return bool
     */
    private static function checkIfFileExists(string $file): bool
    {
        if(file_exists($file)){
            return true;
        }
        return false;
    }

    /**
     * Opens a file
     * @param string $fileName
     * @return
     */
    private static function openFile(string $fileName)
    {
        $file = fopen($fileName, 'a');

        return $file;
    }

    private static function writeToFile($fileName, string $content): bool
    {
        $file = self::openFile(fileName: $fileName);

        if($file !== false){
            fwrite($file, self::$separator . $content . PHP_EOL);
            fclose($file);
            return true;
        }

        return false;
    }
}