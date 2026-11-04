<?php 

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
class CsrfMiddleware
{
    public function handle(Request $req, Response $res): bool
    {
        $method = $req->getMethod();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $headerToken = $req->header('X-XSRF-TOKEN');
        $cookieToken = $_COOKIE['XSRF-TOKEN'] ?? '';

        if (!$headerToken || !hash_equals($cookieToken, $headerToken)) {
            $res->error('CSRF token mismatch', 403)->send();
            return false;
        }
        return true;
    }
}