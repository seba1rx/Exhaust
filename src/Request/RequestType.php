<?php

declare(strict_types=1);

namespace Exhaust\Request;

/**
 * Tipo de request recibido por el framework.
 *
 * Navigation: navegación directa del browser (sin header X-Requested-With).
 * XHR:        XMLHttpRequest clásico (jQuery $.ajax, Axios, etc.).
 * Fetch:      fetch() nativo con header X-Requested-With: fetch.
 *
 * @var string $value valor string que representa el tipo
 */
enum RequestType: string
{
    case Navigation = 'navigation';
    case XHR        = 'xhr';
    case Fetch      = 'fetch';
}
