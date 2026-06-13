<?php

namespace Exhaust\Tools;

final class StringTool
{

    /**
     * gets the substring after the first occurence of the needle
     *
     * example: getStringAfterFirst('o', 'hohoho') will return 'hoho'
     *
     * @param string $needle
     * @param string $haystack
     * @return string|bool
     */
    public static function getStringAfterFirst(string $needle, string $haystack): string|bool
    {
        if(!is_bool(strpos($haystack, $needle))){
            return substr($haystack, strpos($haystack, $needle) + strlen($needle));
        }else{
            return false;
        }
    }

    /**
     * gets the substring after the last occurence of the needle
     *
     * @param string $needle
     * @param string $haystack
     * @return string|bool
     */
    public static function getStringAfterLast(string $needle, string $haystack): string|bool
    {
        $lastPosition = strrpos($haystack, $needle);
        if(!is_bool($lastPosition)){
            return substr($haystack, $lastPosition + strlen($needle), strlen($haystack));
        }else{
            return false;
        }
    }

     /**
     * gets the substring from the start of the haystack to the first occurence of the needle
     *
     * @param string $needle
     * @param string $haystack
     * @return string
     */
    public static function getStringBeforeFirst(string $needle, string $haystack): string
    {
        return substr($haystack, 0, strpos($haystack, $needle));
    }

    /**
     * gets the substring from the start of the haystack to the last occurence of the needle
     *
     * @param string $needle
     * @param string $haystack
     * @return string|bool
     */
    public static function getStringBeforeLast(string $needle, string $haystack): string|bool
    {
        $lastPosition = strrpos($haystack, $needle);
        if(!is_bool($lastPosition)){
            return substr($haystack, 0, $lastPosition);
        }else{
            return false;
        }
    }

    /**
     * get the substring betweeen the first occurrence of the first needle and the last occurrence of the second needle
     *
     * @param string $firstNeedle
     * @param string $secondNeedle
     * @param string $haystack
     * @return string|bool
     */
    public static function getStringBetweenFirstAndLast(string $firstNeedle, string $secondNeedle, string $haystack): string|bool
    {
        $fromFirstNeedleToEnd = self::getStringAfterFirst($firstNeedle, $haystack);

        if(!is_bool($fromFirstNeedleToEnd)){
            return self::getStringBeforeLast($secondNeedle, $fromFirstNeedleToEnd);
        }else{
            return false;
        }
    }

    /**
     * get the substring from the last occurrence of the first needle and the first occurrence of the second needle
     *
     * @param string $firstNeedle
     * @param string $secondNeedle
     * @param string $haystack
     * @return string|bool
     */
    public static function getStringBetweenLastAndFirst(string $firstNeedle, string $secondNeedle, string $haystack): string|bool
    {
        $fromFirstNeedleToEnd = self::getStringAfterLast($firstNeedle, $haystack);
        if(!is_bool($fromFirstNeedleToEnd)){
            return self::getStringBeforeFirst($secondNeedle, $fromFirstNeedleToEnd);
        }else{
            return false;
        }
    }

    /**
     * Evaluates if a string starts with a given char and ends with a given char
     *
     * @param string $haystack
     * @param string $firstChar
     * @param string|null $finalChar if null then the $firstChar will be used for first and final char
     * @return bool
     */
    public static function isWrappedBetween(string $haystack, string $firstChar, string|null $finalChar = null): bool
    {
        if(is_null($finalChar)) $finalChar = $firstChar;
        return str_starts_with($haystack, $firstChar) && str_ends_with($haystack, $finalChar);
    }

    /**
     * generates a random string
     *
     * @param int|null $length
     * @return string
     */
    public static function generateRandomSerial(int|null $length = 10): string
    {
        $charPool = "abccdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $randString = "";
        for($i = 0; $i< $length; $i++){
            $pos = mt_rand(0, strlen($charPool) - 1);
            $randString .= substr($charPool, $pos, 1);
        }
        return $randString;
    }

    /**
     * Evaluates if a string is a valid email string
     *
     * @param string $var
     * @return bool|string
     */
    public static function checkEmail(string $var): bool|string
    {
        $email = strtolower($var);
        if (!preg_match("/^([_[:alnum:]-]+)(\.[_[:alnum:]-]+)*@([[:alnum:]])([[:alnum:]\.-]+)([[:alnum:]])\.([[:alpha:]]{2,4})$/", $email)) {
            return false;
        } else {
            return $email;
        }
    }
}