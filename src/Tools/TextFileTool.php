<?php

namespace Exhaust\Tools;

/**
 * Use this class to manage text files, whether you want to add padded content or just dump content into a txt.
 *
 * @example the following code shows how create a txt file and how to add padded content:
 * $fileGenerator = new TextFileTool();
 * $fileGenerator->createFile('myFile');
 * $pathToFile = $fileGenerator->getFullPathToFile();
 * $fileGenerator->setFillChar(' ');
 * $fileGenerator->addPaddedContent('padded to 20 chars', 20);
 * $fileGenerator->addPaddedContent('padded to 20 chars', 20);
 * $fileGenerator->addPaddedContent('padded to 20 chars', 20);
 * $fileGenerator->nl();
 * $fileGenerator->writeContentToFile();
 * $wasWritten = $fileGenerator->contentWasWritten();
 * if($wasWritten){
 *     error_log("content was not written into the file");
 *     $fileGenerator->removeFile($pathToFile);
 *     exit();
 * }
 * $writtenBytes = $fileGenerator->getWrittenBytes();
 * echo "{$writtenBytes} bytes written in the file located at {$pathToFile}";
 */
class TextFileTool
{
    /**
     * The format of the file to be created, default is txt
     *
     * @var string $fileFormat
     * @access private
     */
    private $fileFormat = 'txt';

    /**
     * The directory where the file will be saved
     *
     * @var string $directoryWhereFileIsSaved
     * @access private
     */
    private $directoryWhereFileIsSaved = "storage/temp";

    /**
     * Absolute path to the file
     *
     * @var string $fullPathToFile
     * @access private
     */
    private $fullPathToFile;

    /**
     * Relative path starting from the app's root to the file, example: "storage/temp/file.txt"
     *
     * @var string $pathToFile
     * @access private
     */
    private $pathToFile;

    /**
     * The resource file, this var contains a pointer to the file
     *
     * @var resource $file
     * @access private
     */
    private $file;

    /**
     * Contains flag indicating if the file is open or not,
     * usefult to know if it can be written or if it is closed.
     *
     * @var bool $fileIsOpen
     * @access private
     */
    private $fileIsOpen = true;

    /**
     * The character(s) used for a new line
     *
     * @var string $nl
     * @access private
     */
    private $nl = "\n";

    /**
     * The mode used to open the file, default is "a+"
     *
     * @see https://www.php.net/manual/es/function.fopen.php
     *
     * @var string $writeMode - set the mode by calling createFile method
     * @access private
     */
    private $writeMode = "a+";

    /**
     * A piece of content to be written into the file
     *
     * @var string $content
     * @access private
     */
    private $content = "";

    /**
     * The char used to fill the content using str_pad
     *
     * @var string $fillChar
     * @access private
     */
    private $fillChar = "0";

    /**
     * The amount of bytes written to the file after fwrite()
     *
     * @var int $writtenBytes
     * @access private
     */
    private $writtenBytes = 0;

    /**
     * A flag to know if the content was written or not into the file
     *
     * @var bool
     * @access private
     */
    private $writeError = false;

    /**
     * Defines a constant to be used to identify if a string must be capitalized
     */
    const STR_TO_UPPER = "STR_TO_UPPER";

    /**
     * Defines a constant to be used to identify if a string must be changed to lower case
     */
    const STR_TO_LOWER = "STR_TO_LOWER";

    /**
     * Constructor of the class
     *
     * @param array|null $conf (['fileFormat' => 'txt'|'md'|'', 'directory' => 'temp'])
     */
    public function __construct(array|null $conf = null)
    {
        if(!is_null($conf)){
            $this->fileFormat = $conf['fileFormat'];
            $this->directoryWhereFileIsSaved = $conf['directory'];
        }
    }

    /**
     * Sets the character(s) used as new line, by default it is set to "\n"
     */
    public function setNewLineString(string $nl): void
    {
        $this->nl = $nl;
    }

    /**
     * Creates the empty file wit the given name
     *
     * @see https://www.php.net/manual/en/function.fopen.php - list of modes for fopen
     *
     * @param string $fileName - name of the file without extension
     * @param string|null $writeMode
     * @return bool
     */
    public function createFile(string $fileName, string|null $writeMode = null): bool
    {
        $this->checkIfSaveDirExists();

        $this->pathToFile = $this->directoryWhereFileIsSaved . "/{$fileName}" . $this->getFileExt();

        $this->fullPathToFile = $_SERVER["DOCUMENT_ROOT"] . "/{$this->pathToFile}";

        if(!is_null($writeMode)){
            $this->writeMode = $writeMode;
        }

        $this->file = fopen($this->pathToFile, $this->writeMode);

        if($this->file === false){
            return false;
        }

        return true;
    }

    /**
     * Checks if directory exists and if it does not exists then it is created and assigned 755 permissions
     *
     * @return void
     */
    private function checkIfSaveDirExists(): void
    {
        $fullPathToDirectory = $_SERVER["DOCUMENT_ROOT"] . "/{$this->directoryWhereFileIsSaved}";
        if (!file_exists($fullPathToDirectory)) {
            $mkdir = mkdir($fullPathToDirectory, 0755);
        }
    }

    /**
     * Returns the extension of the file. Example: ".txt", ".md", ""
     *
     * @return string
     */
    private function getFileExt(): string
    {
        $ext = "";
        switch($this->fileFormat){
            case 'txt': $ext = ".txt"; break;
            case 'md': $ext = ".md"; break;
            default: $ext = ""; break;
        }
        return $ext;
    }

    /**
     * Sets the char used in str_pad function to pad the content
     *
     * @param string $char
     * @return void
     */
    public function setFillChar(string $char): void
    {
        $this->fillChar = $char;
    }

    /**
     * Adds content to the existing $this->content variable
     *
     * @param string $newContent
     * @return void
     */
    public function addContent(string $newContent): void
    {
        $this->content .= $newContent;
    }

    /**
     * Adds content to the existing $this->content variable using str_pad
     *
     * @param string $newContent
     * @param int $length
     * @param ?string|null $caseFormat
     * @return void
     */
    public function addPaddedContent(string $newContent, int $length, ?string $caseFormat = null): void
    {
        if(!is_null($caseFormat)){
            if($caseFormat === self::STR_TO_UPPER){
                $newContent = strtoupper($newContent);
            }
            if($caseFormat === self::STR_TO_LOWER){
                $newContent = strtolower($newContent);
            }
        }
        $paddedContent = str_pad($newContent, $length, $this->fillChar);
        $this->content .= $paddedContent;
    }

    /**
     * Adds the new line to the content
     */
    public function nl(): void
    {
        $this->addContent($this->nl);
    }

    /**
     * Writes the content to the file.
     *
     * @return bool
     */
    public function writeContentToFile(): bool
    {
        if($this->fileIsOpen){
            $written = fwrite($this->file, $this->content);
            if($written === false){
                $this->writeError = true;
                $this->content = "";
                return false;
            }
            $this->writtenBytes = $written;
            $this->content = "";
            return true;
        }
        $this->content = "";
        return false;
    }

    /**
     * Closes the file
     */
    public function closeFile(): void
    {
        if($this->fileIsOpen){
            $closed = fclose($this->file);
            $this->fileIsOpen = $closed ? false : true;
        }
    }

    /**
     * returns the amount of bytes written into the file
     *
     * @return int
     */
    public function getWrittenBytes(): int
    {
        return $this->writtenBytes;
    }

    /**
     * returns a flag indicating if the content was written or not
     *
     * @return bool
     */
    public function contentWasWritten(): bool
    {
        return !$this->writeError;
    }

    /**
     * Returns the absolute path to the file
     *
     * @return string
     */
    public function getFullPathToFile(): string
    {
        return $this->fullPathToFile;
    }

    /**
     * Attempts to remove the file
     *
     * @param string $pathToFile
     * @return bool
     */
    public function removeFile(string $pathToFile): bool
    {
        try{
            if (file_exists($pathToFile)) {
                unlink($pathToFile);
                return true;
            }else{
                return false;
            }

        }catch(\Exception $e){
            // fails silently
            return false;
        }
    }

}