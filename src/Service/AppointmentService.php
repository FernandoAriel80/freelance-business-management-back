<?php

namespace Src\Service;

use Core\Support\Date;
use RuntimeException;
use Src\Repository\AppointmentRepository;

class AppointmentService
{
    public function __construct(private AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function createAppointment($data)
    {
        $payload = [
            ...$data,
            'duration_minutes' => (int) $data['duration_minutes'] ?? null,
            'reminder_at' => $data['reminder_sent'] == 1 ? Date::now() : null
        ];

        $created = $this->appointmentRepository->create($payload);
        if (!$created)
            throw new RuntimeException('Error al crear cita.');
        return $created;
    }
}