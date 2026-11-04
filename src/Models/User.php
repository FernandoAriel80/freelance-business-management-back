<?php

namespace Src\Models;

use Core\Database\ORM;
use PDO;

class User extends ORM
{
    public function __construct(private PDO $connection)
    {
        parent::__construct('users', $connection);
    }


}


/* 
class User extends ORM
{

   public function __construct(PDO $connection)
    {
        parent::__construct('users', $connection);
    }
public function posts(): array
{
    return $this->hasMany('posts', 'user_id', $this->getId());
}

public function roles(): array
{
    return $this->belongsToMany('roles', 'role_user', 'user_id', 'role_id', $this->getId());
}
}

$user = $this->user->find($id);
if (!$user) return [];

return [
'user'  => $user->toArray(),   // ✅ array limpio para JSON
'posts' => $user->posts(),
'roles' => $user->roles(),
];

*/