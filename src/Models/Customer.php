<?php

namespace Src\Models;

use Core\Database\ORM;
use PDO;

class Customer extends ORM
{
    public function __construct(private PDO $conection)
    {
        parent::__construct('customers', $conection);
    }
}