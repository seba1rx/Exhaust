<?php

namespace Exhaust\Logging;

/**
 * @see https://stackoverflow.com/questions/34034730/how-to-enable-color-for-php-cli
 */
final class CliLog
{

    private $handler;

    protected function __construct()
    {
        
    }

    /**
     * Outputs a colored text to CLI
     *
     * @param string $str
     * @param string $type ['i', 'w', 's', 'e']
     * @return string
     * @see https://stackoverflow.com/questions/34034730/how-to-enable-color-for-php-cli
     */
    public static function colorLog(string $str, string $type = 'i'): string
    {
        $colors = [
            'e' => 31, //error
            's' => 32, //success
            'w' => 33, //warning
            'i' => 36  //info
        ];
        $color = $colors[$type] ?? 0;
        return "\033[".$color."m".$str."\033[0m\n";
    }

    /**
     * Outputs an info colored text to CLI
     *
     * @param string $str
     * @return string
     */
    public static function info(string $str): string
    {
        $color = 36; // info
        return "\033[".$color."m".$str."\033[0m\n";
    }

    /**
     * Outputs a warninig colored text to CLI
     *
     * @param string $str
     * @return string
     */
    public static function warning(string $str): string
    {
        $color = 33; // warning
        return "\033[".$color."m".$str."\033[0m\n";
    }

    /**
     * Outputs a success colored text to CLI
     *
     * @param string $str
     * @return string
     */
    public static function success(string $str): string
    {
        $color = 32; // success
        return "\033[".$color."m".$str."\033[0m\n";
    }

    /**
     * Outputs an error colored text to CLI
     *
     * @param string $str
     * @return string
     */
    public static function error(string $str): string
    {
        $color = 31; // error
        return "\033[".$color."m".$str."\033[0m\n";
    }
}