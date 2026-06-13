<?php

namespace Exhaust;

use Exhaust\Controllers;

class Manager
{
    public function showDocumentation(): array
    {
        $html_content = app()->render(
            'landing_page/documentation.html.twig',
            ['DEBUG_FRONTEND' => DEBUG_FRONTEND]
        );

        return [
            "response_type" => "html",
            "html" => $html_content,
        ];
    }


}