<?php

declare(strict_types=1);

namespace Exhaust\Controllers;

use Exhaust\Response\Commands;

/**
 * Internal controller used to display the framework's own documentation landing page.
 * This is not a base class for the consuming app's controllers.
 */
class Manager
{
    /**
     * Renders the framework documentation page and returns it as an "echo" command
     *
     * @return array
     */
    public function showDocumentation(): array
    {
        $html_content = app()->render(
            'landing/documentation.html.twig',
            ['DEBUG_FRONTEND' => DEBUG_FRONTEND]
        );

        Commands::echo($html_content);

        return Commands::all();
    }
}
