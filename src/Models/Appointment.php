<?php 

namespace Src\Models;

use Core\Database\ORM;
use PDO;

class Appointment extends ORM {
    public function __construct(PDO $connection) {
        parent::__construct('appointments', $connection);
    }
}