<?php

declare(strict_types=1);

namespace Exhaust\Contracts;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Contract for all framework middlewares.
 *
 * Extends PSR-15 MiddlewareInterface, providing full interoperability with
 * the PHP middleware ecosystem (CORS libraries, rate-limiters, auth packages,
 * and any other library that targets the PSR-15 standard).
 *
 * A middleware receives a PSR-7 ServerRequestInterface and a PSR-15
 * RequestHandlerInterface. It must return a PSR-7 ResponseInterface — either
 * by delegating to the next handler or by producing its own response.
 *
 * Use Request::toPsr7($factory) to convert the framework request into a
 * PSR-7 ServerRequest when calling PSR-15-aware middleware directly.
 *
 * ---
 *
 * PSR-15 compliant (recommended for new middlewares):
 *
 *   use Exhaust\Contracts\MiddlewareBlueprint;
 *   use Psr\Http\Message\ResponseInterface;
 *   use Psr\Http\Message\ServerRequestInterface;
 *   use Psr\Http\Server\RequestHandlerInterface;
 *
 *   class AuthMiddleware implements MiddlewareBlueprint
 *   {
 *       public function process(
 *           ServerRequestInterface  $request,
 *           RequestHandlerInterface $handler,
 *       ): ResponseInterface
 *       {
 *           if (!isset($_SESSION['user'])) {
 *               // short-circuit: return a redirect response
 *               return new RedirectResponse('/login');
 *           }
 *           // continue to the next middleware / controller
 *           return $handler->handle($request);
 *       }
 *   }
 *
 * ---
 *
 * Legacy invokable pattern (still supported for backward compatibility):
 *
 *   class AuthMiddleware
 *   {
 *       public function __invoke(): void
 *       {
 *           if (!isset($_SESSION['user'])) {
 *               header('Location: /login');
 *           }
 *       }
 *   }
 *
 * ---
 *
 * @see https://www.php-fig.org/psr/psr-15/
 */
interface MiddlewareBlueprint extends MiddlewareInterface {}
