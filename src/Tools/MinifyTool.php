<?php

namespace Exhaust\Tools;

class MinifyTool
{

    /**
     * The content that is being processed
     * @var string
     */
    private static $content;

    /**
     * Indicates if a content has html pre tags
     * @var bool
     */
    private static $hasPreBlocks = false;

    /**
     * The content of the pre blocks
     * @var array
     */
    private static $preBlocks;


    /**
     * Minifies JS code
     *
     * @param string $js
     * @return string
     */
    public static function js(string $js): string
    {
        $js = self::removeJavascriptComments($js);
        $js = self::fixMalformedJsonInScript($js);
        self::$content = $js;
        self::removeNewLinesAndWhiteSpaces();
        return self::$content;
    }

    /**
     * Minifies HTML code
     *
     * @param string $html
     * @return string
     */
    public static function html(string $html): string
    {
        return self::minify($html);
    }

    /**
     * Minifies HTML or JS content by removing new lines and blank spaces so
     * it uses less space / reduce response time
     *
     * @param string $content
     * @return string
     */
    public static function minify(string $content): string
    {

        self::$content = $content;

        self::removeNewLinesAndWhiteSpaces();

        // if content has pre tags <pre></pre> replace them for placeholders
        $has_pre_tags = self::checkIfContentHasPreTags();
        if($has_pre_tags){
            self::replacePreTagsForPlaceHolders();
        }

        // if content has html comments, remove comments
        if(self::checkIfContentHasHtmlComments()){
            self::removeHtmlComments();
        }

        // if content has script tags, process those script tags
        if(self::checkIfContentHasScriptTags()){
            self::joinScriptContent();
        }

        if($has_pre_tags){
            self::restorePreBlocks();
        }

        self::removeHtmlWhiteSpaces();

        return self::$content;

    }

    /**
     * Remodes new lines and repeated white spaces from content
     *
     * @return void
     */
    private static function removeNewLinesAndWhiteSpaces(): void
    {
        $search = array(
            '/(\n|^)(\x20+|\t)/', // spaces or tabs at the beginning of a line
            '/\n/', // line breaks
            '/(\x20+|\t)/', // multiple white spaces
        );

        $replace = array(
            "\n",
            " ",
            " ",
        );

        // html or js or both
        self::$content = preg_replace($search, $replace, self::$content);
    }

    /**
     * Removes white spaces in html content
     *
     * @return void
     */
    private static function removeHtmlWhiteSpaces(): void
    {
        $search = array(
            '/\>\s+\</', // strip whitespaces between tags. turn <> <> into <><>
            '/(\"|\')\s+\>/', // strip whitespaces between quotation ("') and end tags. turns class="test" > into class="test">
            '/=\s+(\"|\')/' // strip whitespaces after equal sign followed by space, turn class= "test"> into class="test">
        );

        $replace = array(
            "><",
            "$1>",
            "=$1"
        );

        // html or js or both
        self::$content = preg_replace($search, $replace, self::$content);
    }

    /**
     * Remove js comment
     *
     * @return void
     */
    private static function removeJavascriptComments($code): string
    {
        // regex to remove:
        // - /** ... */
        // - /* ... */
        $code = preg_replace('/\/\*[\s\S]*?\*\//', '', $code);

        // remove comments that start with // ... (until the end of the line)
        $code = preg_replace('/\/\/[^\n\r]*/', '', $code);

        // single line
        $code = str_replace("\r", '', $code);


        return $code;
    }


    /**
     * Checks if $content has script tags
     *
     * @return bool
     */
    private static function checkIfContentHasScriptTags(): bool
    {
        $hasScriptOpen = str_contains(self::$content, "<script>");
        $hasScriptClose = str_contains(self::$content, "</script>");
        return $hasScriptOpen && $hasScriptClose;
    }

    /**
     * Checks if $content has html comments
     *
     * @return bool
     */
    private static function checkIfContentHasHtmlComments(): bool
    {
        $hasCommentOpen = str_contains(self::$content, "<!--");
        $hasCommentClose = str_contains(self::$content, "-->");
        return $hasCommentOpen && $hasCommentClose;
    }

    /**
     * Checks if $content has <pre></pre> tags
     *
     * @return bool
     */
    private static function checkIfContentHasPreTags(): bool
    {
        $hasPreTagOpen = str_contains(self::$content, "<pre>");
        $hasPreTagClose = str_contains(self::$content, "</pre>");
        self::$hasPreBlocks = $hasPreTagOpen && $hasPreTagClose;
        return $hasPreTagOpen && $hasPreTagClose;
    }

    /**
     * Removes comas (,) before a closing '}'
     *
     * + example: ('foo', }) -> ('foo' })
     *
     * @see https://stackoverflow.com/questions/201782/can-you-use-a-trailing-comma-in-a-json-object
     *
     * @param string $script
     * @return string
     */
    private static function fixMalformedJsonInScript(string $code): string
    {
        // removes white spaces before a closing '}' even if there is a new line
        return preg_replace('/,(\s*})/', '$1', $code);
    }

    /**
     * Unifies javascript content under one script tag when minifying html
     *
     * @return string
     */
    private static function joinScriptContent(): void
    {
        // content inside normal script tags: <script></script>
        // ignore script tags with src attribute: <script src="file.js"></script>
        $pattern1 = '/<script(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/is';

        preg_match_all($pattern1, self::$content, $code_within_script_tags);

        $single_script = "";
        foreach ($code_within_script_tags[1] as $scriptContent) {

            // single line
            $scriptContent = str_replace("\r", '', $scriptContent);

            // fix trailing comas in json
            $scriptContent = self::fixMalformedJsonInScript($scriptContent);

            // remove comments
            $scriptContent = self::removeJavascriptComments($scriptContent);

            // unifies scripts from different script tags
            $single_script .= $scriptContent;
        }

        // remove normal script tag content without touching script with src attribute
        $pattern2 = '/<script(?![^>]*\bsrc=)[^>]*>.*?<\/script>/is';
        $content = preg_replace($pattern2, '', self::$content);

        // add a single normal script tag with the unified js content at the end
        $content .= "<script>";
        $content .= $single_script;
        $content .= "</script>";

        self::$content = $content;
    }

    /**
     * Remove html comments
     *
     * @return void
     */
    private static function removeHtmlComments(): void
    {
        self::$content = preg_replace('/<!--[\s\S]*?-->/', '', self::$content);
    }

    /**
     * Replace <pre></pre> tags for a placeholder
     *
     * @return void
     */
    private static function replacePreTagsForPlaceHolders(): void
    {
        $preBlocks = [];
        $content_with_pre_placeholders = preg_replace_callback(
            '/<pre\b[^>]*>.*?<\/pre>/is',
            function ($matches) use (&$preBlocks) {
                $placeholder = "__PRE_BLOCK_" . count($preBlocks) . "__";
                $preBlocks[$placeholder] = $matches[0];
                return $placeholder;
            },
            self::$content
        );

        self::$preBlocks = $preBlocks;
        self::$content = $content_with_pre_placeholders;
    }

    /**
     * Restores pre tags placeholders with the original content
     * @return void
     */
    private static function restorePreBlocks(): void
    {
        // Restore <pre> blocks
        foreach (self::$preBlocks as $placeholder => $original) {
            self::$content = str_replace($placeholder, $original, self::$content);
        }
    }


}