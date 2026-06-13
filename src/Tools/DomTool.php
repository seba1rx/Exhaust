<?php

namespace Exhaust\Tools;

use DOMWrap\Document;

/**
 * This class is a wrapper of the DOMWrap\Document tool (which is already a wrapper)
 * by selecting a curated list of methods to manipulate the dom
 *
 * @see https://github.com/scotteh/php-dom-wrapper
 */
class DomTool
{
    /**
     * The Document object
     * @var Document
     */
    private $dom;

    public static function init(string $input): void
    {
        if (!isset(self::$dom)) self::$dom = new Document();
        self::$dom->html($input);
    }

    public static function reset(string $input): void
    {
        self::$dom = new Document();
        self::$dom->html($input);
    }

    public static function append(string $input, string $selector, string $append): string
    {
        self::init($input);
        return self::$dom->find($selector)->appendWith($append);
    }

    public static function prepend(string $input, string $selector, string $append): string
    {
        self::init($input);
        return self::$dom->find($selector)->prependWith($append);
    }

    public static function setAttribute(string $input, string $attribute, string $value): string
    {
        self::init($input);
        return self::$dom->attr($attribute, $value);
    }

    public static function destroy(string $input, string $selector): string
    {
        self::init($input);
        return self::$dom->find($selector)->destroy();
    }

    public static function removeClass(string $input, string $selector, string $class): string
    {
        self::init($input);
        return self::$dom->find($selector)->removeClass($class);
    }

    public static function hasClass(string $input, string $selector, string $class): bool
    {
        self::init($input);
        return self::$dom->find($selector)->hasClass($class);
    }

}

