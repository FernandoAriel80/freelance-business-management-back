<?php
namespace Src\Controllers;

use Core\Http\Request;
use Core\Http\Response;
class EjemploController
{
    public function index(Request $req, Response $res)
    {
        return $res->success(['message' => 'Hola desde API'])->send();
    }
}