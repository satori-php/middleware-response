<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2017 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Middleware\Response;

use Satori\Application\ApplicationInterface;
use Satori\Http\Response;

/**
 * @var int Number of seconds in a month (30 days).
 */
const MONTH = 60 * 60 * 24 * 30;

/**
 * Initializes the response middleware.
 *
 * @param ApplicationInterface  $app   The application.
 * @param string                $id    The unique name of the middleware.
 * @param array<string, string> $names 
 *    The array with names
 *      ```
 *      [
 *          'cookie_lifetime' => 'cookie.lifetime',
 *          'cookie_path' => 'cookie.path',
 *          'cookie_domain' => 'domain.admin',
 *          'cookie_secure' => 'cookie.secure',
 *          'cookie_httponly' => 'cookie.httponly'
 *      ]
 *      ```
 *      .
 */
function init(ApplicationInterface $app, string $id, array $names)
{
    $app[$id] = function (\Generator $next) use ($app, $names) {
        $app->notify('start_response');
        $capsule = yield;
        $defaultLifetime = $app[$names['cookie_lifetime'] ?? ''] ?? MONTH;
        $defaultPath = $app[$names['cookie_path'] ?? ''] ?? '/';
        $defaultDomain = $app[$names['cookie_domain'] ?? ''] ?? '';
        $defaulSecure = $app[$names['cookie_secure'] ?? ''] ?? false;
        $defaulHttponly = $app[$names['cookie_httponly'] ?? ''] ?? true;
        Response\sendStatusLine('1.1', $capsule['http.status']);
        Response\sendHeaders($capsule['http.headers']);
        foreach ($capsule['cookies'] ?? [] as $name => $params) {
            $lifetime = ($params['lifetime'] ?? $defaultLifetime) + time();
            $path = $params['path'] ?? $defaultPath;
            $domain = $params['domain'] ?? $defaultDomain;
            $secure = $params['secure'] ?? $defaulSecure;
            $httponly = $params['httponly'] ?? $defaulHttponly;
            setcookie($name, $params['value'], $lifetime, $path, $domain, $secure, $httponly);
        }
        if (isset($capsule['http.body']) && $capsule['http.body']) {
            Response\sendBody($capsule['http.body']);
        }
        $app->notify('finish_response');
        $next->send($capsule);

        return $next->getReturn();
    };
}
