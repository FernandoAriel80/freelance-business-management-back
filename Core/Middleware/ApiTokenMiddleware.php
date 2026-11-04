<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Security\SanctumAPI;
use Core\Support\Log;
use Src\Models\Token;

class ApiTokenMiddleware
{
    private SanctumAPI $api;
    public function __construct(
        private Token $token,
    ) {
        $this->token = $token;
        $this->api = new SanctumAPI($this->token);
    }

    public function handle(Request $req, Response $res): bool
    {
        $userId = $this->api->verifyFromHeader($req->header('Authorization'));
        if (!$userId) {
            Log::debug('Token no proporcionado o inválido');
            $res->error('Token no proporcionado o inválido', 401)->send();
            return false;
        }

        $req->setPathParams(['auth_user_id' => $userId]);
        return true;
    }
}