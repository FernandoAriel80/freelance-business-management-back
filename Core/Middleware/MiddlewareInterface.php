<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $req, Response $res): bool;
}