<?php
namespace Src\Auth;

use Core\Security\Hash;
use Core\Security\Uuid;
use Core\Support\Date;
use Core\Support\Str;
use Src\Repository\UserRepository;
use Exception;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function register($data)
    {
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new \RuntimeException('El email ya está registrado.');
        }

        $payload = [
            'id' => Uuid::v4(),
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'user',
            'email_verified_at' => Date::now(),
            'password' => Hash::make($data['password']),
            'verification_token' => null,
            'active' => 0,
            'created_at' => Date::now(),
        ];

        $user = $this->userRepository->create($payload);
        if (!$user) {
            throw new Exception('Error al crear un usuario.');
        }
        return $user;
    }

    public function login($data)
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            throw new \RuntimeException('Credenciales inválidas.');
        }

        if (empty($user['email_verified_at'])) {
            throw new \RuntimeException('Debes verificar tu correo antes de iniciar sesión.');
        }

        return $user;
    }

    public function getDataUser($id)
    {
        $user = $this->userRepository->findById($id);
        if (!$user)
            throw new \RuntimeException('Usuario no encontrado');

        return $user;
    }

}