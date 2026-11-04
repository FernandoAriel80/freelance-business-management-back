<?php

namespace Src\Service;

use RuntimeException;
use Src\Repository\UserRepository;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function getAll()
    {
        $users = $this->userRepository->allPagination();
        if (!$users)
            throw new RuntimeException("Error al encontrar los usuarios paginados.");

        return $users;
    }
}