<?php

namespace Src\Service;

use Core\Security\Uuid;
use Core\Support\Date;
use Core\Support\Log;
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
            'id' => Uuid::v4(),
            'duration_minutes' => (int) $data['duration_minutes'] ?? null,
            'reminder_sent' => $data['reminder_at'] != null ? 1 : 0
        ];

        $created = $this->appointmentRepository->create($payload);
        if (!$created)
            throw new RuntimeException('Error al crear cita.');
        return $created;
    }

    public function getAll($currentPage, $query, $filters, $sort)
    {
        $perPage = 8;

        $appointments = $this->appointmentRepository->all($currentPage, $perPage, $query, $filters, $sort);
        if (!$appointments)
            throw new RuntimeException('Error al obtener las citas.');

        $data = [];

        foreach ($appointments['data'] as $value) {
            $data[] = [
                ...$value,
                'starts_at' => $value['starts_at'] != null ? Date::format($value['starts_at'], 'd/m/Y H:i:s') : null,
                'reminder_at' => $value['reminder_at'] != null ? Date::format($value['reminder_at'], 'd/m/Y H:i:s') : null,
                'ends_at' => $value['ends_at'] != null ? Date::format($value['ends_at'], 'H:i:s') : null,
                'created_at' => Date::toLocal($value['created_at'], 'd/m/Y H:i:s'),
            ];
        }

        $meta = $appointments['meta'];
        $payload = [
            'data' => $data,
            'meta' => $meta,
        ];
        return $payload;
    }

    public function getLastsCustomer($customerId)
    {
        $limit = 2;
        $appointments = $this->appointmentRepository->getlastsCustomers($customerId, $limit);
        return !$appointments ? [] : $appointments;
    }

    public function getAllByCustomer($customerId)
    {
        $appointments = $this->appointmentRepository->getAllByCustomer($customerId);
        return !$appointments ? [] : $appointments;
    }

    public function updateAppointment($id, $data)
    {
        $existing = $this->appointmentRepository->findById($id);
        if (!$existing)
            throw new RuntimeException('Error al encontrar reunión.');

        $payload = [
            ...$data,
            'starts_at' => $data['starts_at'] != null ? Date::format($data['starts_at'], 'Y/m/d H:i:s') : null,
            'ends_at' => $data['ends_at'] != null ? Date::format($data['ends_at'], 'H:i:s') : null,
            'reminder_at' => $data['reminder_at'] != null ? Date::format($data['reminder_at'], 'Y/m/d H:i:s') : null,
            'reminder_sent' => $data['reminder_at'] != null ? 1 : 0,
        ];

        $updated = $this->appointmentRepository->update($id, $payload);
        if (!$updated)
            throw new RuntimeException('Error al actualziar una reunión');
        return $updated;
    }
}