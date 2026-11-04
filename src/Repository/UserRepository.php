<?php
namespace Src\Repository;

use Src\Models\User;

class UserRepository
{
    public function __construct(private User $user)
    {
        $this->user = $user;
    }

    public function allPagination()
    {
        return $this->user->paginate(1, 15, ['id', 'name', 'email', 'role', 'active', 'created_at']);
    }

    public function findById($id)
    {
        return $this->user->find($id);
    }

    public function create(array $data)
    {
        return $this->user->create($data);
    }

    public function findByEmail(string $email)
    {
        return $this->user->where('email', '=', $email)->first();
    }
    public function update($id, $data)
    {
        return $this->user->update($id, $data);
    }

    public function findByToken($token)
    {
        return $this->user->where('verification_token', '=', $token)->first();
    }

    /* public function verifyEmail(string $token): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE verification_token = ?"
        );
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    } */
}