<?php
namespace Src\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Src\Service\AdminService;

class AdminController
{
    public function __construct(
        private AdminService $adminService,
    ) {
        $this->adminService = $adminService;
    }
    public function register(Request $req, Response $res)
    {
        return $res->success(['message' => 'Admin register (protegido)'])->send();
    }

    public function get(Request $req, Response $res)
    {
        try {
            $paginated = $this->adminService->getAll();

            return $res->success([
                'data' => $paginated['data'],
                'meta' => $paginated['meta'],
            ])->send();
        } catch (\RuntimeException $e) {
            return $res->error($e->getMessage(), 409)->send();
        }
    }

    public function activeUser(Request $req, Response $res)
    {
        try {
            $id = $req->param('id');

            $this->adminService->activeUser($id);

            return $res->success(['message' => 'Usuario activado exitosamente.'])->send();
        } catch (\RuntimeException $e) {
            return $res->error($e->getMessage(), 409)->send();

        }
    }
}