<?php

namespace Src\Repository;

use Src\Models\Appointment;

class AppointmentRepository
{
    public function __construct(private Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function create($data)
    {
        return $this->appointment->create($data);
    }
}