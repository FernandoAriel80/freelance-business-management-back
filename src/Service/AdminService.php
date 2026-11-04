<?php

namespace Src\Service;

use RuntimeException;
use Src\Repository\UserRepository;

class AdminService
{
    public function __construct(private UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAll()
    {
        $users = $this->userRepository->allPagination();
        if (!$users)
            throw new RuntimeException("Error al encontrar los usuarios paginados.");

        return $users;
    }

    public function activeUser($id)
    {
        $user = $this->userRepository->findById($id);
        if (!$user)
            throw new RuntimeException('Error al buscar usuario');

        $updated = $this->userRepository->update($user['id'], [
            'active' => $user['active'] == 0 ? 1 : 0,
        ]);
        if (!$updated)
            throw new RuntimeException("Error al activar usuario.");

        return $user;
    }
}