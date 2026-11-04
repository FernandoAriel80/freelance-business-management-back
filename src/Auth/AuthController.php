<?php

namespace Src\Auth;

use Core\Http\Request;
use Core\Http\Response;
use Core\Security\SanctumAPI;
use Src\Models\Token;
use Src\Auth\AuthService;

class AuthController
{
    private SanctumAPI $api;

    public function __construct(
        private AuthService $authService,
        private Token $token
    ) {
        $this->api = new SanctumAPI($this->token);
    }

    public function profile(Request $req, Response $res)
    {
        try {
            $userId = $req->input('auth_user_id');  // lo pone ApiTokenMiddleware

            $user = $this->authService->getDataUser($userId);
            if (!$user) {
                return $res->error('Usuario no encontrado', 404)->send();
            }
            // Devuelve solo los campos públicos (nunca el password)
            return $res->success([
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'active' => $user['active'],
            ])->send();
        } catch (\Core\Validation\ValidationException $e) {
            return $res->json(['errors' => $e->errors], 422)->send();
        } catch (\RuntimeException $e) {
            return $res->error($e->getMessage(), 409)->send();
        }
    }

    // REGISTER: crea el usuario, NO devuelve token
    public function register(Request $req, Response $res)
    {
        try {
            $data = $req->validate([
                'name' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'name.required' => 'El nombre es requerido.',
                'email.required' => 'El correo es requerido.',
                'email.email' => 'No es un correo valido.',
                'password.required' => 'La contraseña es requerido.',
                'password.min' => 'El minimo de caracteres para una contraseña es de 8.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);

            $result = $this->authService->register($data);

            // No devolvemos token, el usuario debe hacer login
            return $res->success(['message' => 'Usuario registrado. Ahora inicia sesión.'], 201)->send();

        } catch (\Core\Validation\ValidationException $e) {
            return $res->json(['errors' => $e->errors], 422)->send();
        } catch (\RuntimeException $e) {
            return $res->error($e->getMessage(), 409)->send();
        }
    }

    // LOGIN: valida credenciales y devuelve el token
    public function login(Request $req, Response $res)
    {
        try {
            $data = $req->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ], [
                'email.required' => 'El correo es requerido.',
                'email.email' => 'No es un correo valido.',
                'password.required' => 'La contraseña es requerido.',
                'password.min' => 'El minimo de caracteres para una contraseña es de 8.'
            ]);

            $user = $this->authService->login($data);
            $plainToken = $this->api->createToken($user['id'], 'web-login');

            return $res->success([
                'token' => $plainToken,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ],
            ])->send();

        } catch (\Core\Validation\ValidationException $e) {
            return $res->json(['errors' => $e->errors], 422)->send();
        } catch (\RuntimeException $e) {
            return $res->error($e->getMessage(), 401)->send();
        }
    }

    // LOGOUT: revoca el token actual
    public function logout(Request $req, Response $res)
    {
        $this->api->revokeFromHeader($req->header('Authorization'));
        return $res->success(['message' => 'Sesión cerrada'])->send();
    }
}