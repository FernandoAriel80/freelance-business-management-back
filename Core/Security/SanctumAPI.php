<?php
namespace Core\Security;

use Core\Support\Date;
use Src\Models\Token;
use Core\Security\Uuid;

class SanctumAPI
{
    public function __construct(private Token $token)
    {
        $this->token = $token;
    }

    /**
     * Crea un token para un usuario. Devuelve el token en texto plano (solo una vez).
     */
    public function createToken(string $userId, string $name = 'api-token'): string
    {
        $plainToken = bin2hex(random_bytes(32)); // Token opaco de 64 caracteres
        $tokenHash = hash('sha256', $plainToken);

        $this->token->create([
            'id' => Uuid::v4(),
            'user_id' => $userId,
            'name' => $name,
            'token_hash' => $tokenHash,
            'created_at' => Date::now(),
        ]);

        return $plainToken; // El cliente debe guardarlo
    }

    /**
     * Verifica un token Bearer y devuelve el user_id si es válido, o null.
     */
    public function verifyToken(string $plainToken): ?string
    {
        $tokenHash = hash('sha256', $plainToken);

        $token = $this->token->where('token_hash', '=', $tokenHash)->first(['user_id']);

        return $token ? $token['user_id'] : null;
    }

    /**
     * Revoca un token (logout para clientes con token).
     */
    public function revokeToken(string $plainToken): bool
    {
        $tokenHash = hash('sha256', $plainToken);
        $token = $this->token->where('token_hash', '=', $tokenHash)->first();
        $deleted = $this->token->delete($token['id']);

        return !$deleted ? false : true;
    }

    /**
     * Extrae el token Bearer de una cabecera "Authorization".
     * Devuelve null si no hay cabecera o no tiene el formato correcto.
     */
    public static function extractBearerToken(?string $authorizationHeader): ?string
    {
        if (!$authorizationHeader) {
            return null;
        }

        if (preg_match('/^Bearer\s+(\S+)$/i', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Verifica el token que viene en la cabecera Authorization de la request.
     * Devuelve el user_id si es válido, o null.
     *
     * @param string|null $authorizationHeader El valor de $req->header('Authorization')
     */
    public function verifyFromHeader(?string $authorizationHeader): ?string
    {
        $token = self::extractBearerToken($authorizationHeader);
        if (!$token) {
            return null;
        }
        return $this->verifyToken($token);
    }


    /**
     * Revoca el token que viene en la cabecera Authorization.
     * Devuelve true si se revocó, false si no había token o no existía.
     */
    public function revokeFromHeader(?string $authorizationHeader): bool
    {
        $token = self::extractBearerToken($authorizationHeader);
        if (!$token) {
            return false;
        }
        return $this->revokeToken($token);
    }
}

/* 

public function token(Request $req, Response $res)
{
    $data = $req->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $user = $this->userRepository->findByEmail($data['email']);
    if (!$user || !password_verify($data['password'], $user['password'])) {
        return $res->error('Credenciales inválidas', 401)->send();
    }

    $api = new \Core\Security\SanctumAPI($this->db);
    $plainToken = $api->createToken($user['id']);

    return $res->success(['token' => $plainToken])->send();
}*/


/* 

class ApiTokenMiddleware
{
    public function handle(Request $req, Response $res): bool
    {
        $header = $req->header('Authorization') ?? '';
        if (!preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
            $res->error('Token no proporcionado', 401)->send();
            return false;
        }

        $api = new \Core\Security\SanctumAPI($this->db);
        $userId = $api->verifyToken($matches[1]);

        if (!$userId) {
            $res->error('Token inválido', 401)->send();
            return false;
        }

        // Guardas el user_id en la request para que el controlador lo use
        $req->setPathParams(['user_id' => $userId]);
        return true;
    }
}*/