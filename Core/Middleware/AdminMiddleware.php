<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Support\Log;
use Src\Models\User;

class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(private User $user)
    {
        $this->user = $user;
    }
    public function handle(Request $req, Response $res): bool
    {
        $userId = $req->input('auth_user_id');  
        if (!$userId) {
            Log::debug('No autenticado');
            $res->error('No autenticado', 401)->send();
            return false;
        }

        $user = $this->user->find($userId);
        if (!$user || $user['role'] !== 'admin') {
            Log::debug('Acceso denegado: se requiere rol admin');
        $res->error('Acceso denegado: se requiere rol admin', 403)->send();
            return false;
        }

        $req->setUser($user);  // por si lo necesitas después
        return true;
    }
}