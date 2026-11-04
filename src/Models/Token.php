<?php

namespace Src\Models;

use Core\Database\ORM;
use PDO;

class Token extends ORM
{
    public function __construct(private PDO $connection)
    {
        parent::__construct('tokens', $connection);
    }


}