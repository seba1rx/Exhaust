<?php

declare(strict_types=1);

namespace Exhaust\Logging;

use Exhaust\Tools\StringTool;
use Exhaust\App;

/**
 * TODO: modificar toda esta clase o descartarla
 * hacerla singleton o invocable;
 */
final class QueryLogger{

    private $app;

    public function __construct(string $outputPath = '/Log/logFiles/queries')
    {
        $this->app = App::getInstance();

        ## si outputPath no tiene el slash al inicio, se lo agrega
        if (!str_starts_with($outputPath, '/')) {
            $outputPath = '/' . $outputPath;
        }

        /**
         * when running from cli this app wont be served by a server, just executed by php as a script
         * so $_SERVER['DOCUMENT_ROOT'] will be equal to empty string.
         * only when served by a server as apache or nginex it will hold the root path.
         */
        $document_root = "";
        $document_root_directory_name = "";
        if(empty($_SERVER['DOCUMENT_ROOT'])){
            ## CLI runtime
            $use = $app->conf->root_directory->use;
            $document_root_directory_name = $app->conf->root_directory->root->{$use};
        }else{
            ## web server runtime

            ## si document root termina en /public o en \public hay que quitar esa ultima parte,
            ## debemos obtener el nombre del directorio donde estÃ¡n las fuentes
            $public = ["/public", "/public/", "\public", "\public\\"];
            $server_document_root = $_SERVER['DOCUMENT_ROOT'];
            $server_document_root = str_replace($public,'', $server_document_root);

            ## si server_document_root es empty no fue posible determinarlo, no es posible continuar, se lanza exception
            if(empty($server_document_root)){
                throw new \Exception("No se ha podido determinar el directorio raiz, no es posible continuar");
            }

            ## identifico si usa "/" o si usa "\"
            $directory_separator = "\\";
            if(str_contains($server_document_root, "/")){
                $directory_separator = "/";
            }

            ## nos quedamos solo con la ultima parte que corresponde al nombre del directorio de las fuentes
            $document_root_directory_name = StringTool::getStringAfterLast($directory_separator, $server_document_root);

        }

        $current_file_and_its_path = realpath(__FILE__);
        $document_before_root = StringTool::getStringBeforeLast($document_root_directory_name, $current_file_and_its_path);

        ## si $document_before_root no termina con '/' o '\' se le agrega '/' o '\'
        ## se vuelve a evaluar acÃ¡, ya que en cli solo evalua en este punto y en apache lo vuelve a evaluar de todas formas
        $directory_separator = str_contains($document_before_root, '/') ? '/' : '\\';
        $document_root = $document_before_root . $document_root_directory_name;
        if(!(!str_ends_with($document_before_root, $directory_separator) || !str_ends_with($document_before_root, $directory_separator))){
            $document_root .= "\\";
        }

        ## public: se compone string usando el separador correcto de directorio
        $public = $directory_separator . 'public';

        ## si directory_separator es backslash se cambia el separador usado en outputPath
        if($directory_separator != '/'){
            $outputPath = str_replace("/", "\\", $outputPath);
        }

        ## dependiendo de la config del server, el DOCUMENT_ROOT puede ser o no ser public, si fuera, se sube un nivel
        $this->project_root = $document_root;
        if (str_ends_with($document_root, $public)) {
            $this->project_root =  substr_replace($this->project_root, '', strrpos($this->project_root, $public));
        }

        ## si proyect_root termina con $directory_separator, quitar el directory separator de $outputPath al inicio del string if any
        if(str_ends_with($this->project_root, $directory_separator) && str_starts_with($outputPath, $directory_separator)){
            $outputPath = ltrim($outputPath, $directory_separator);
        }

        $this->log_file_path = $this->project_root . $outputPath;
        $this->curdate = date('Ymd');
        $this->curtime = date('Y-m-d H:i:s');
        $this->addHeaderLine();
    }
}