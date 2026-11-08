<?php

namespace Src\Repository;

use Src\Models\Appointment;

class AppointmentRepository
{
    public function __construct(private Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function all($page, $perPage, $query = '', array $filters = [], array $sort = ['created_at', 'DESC'])
    {
        [$sortColumn, $sortDirection] = $sort;
        $qb = $this->appointment->orderBy($sortColumn, $sortDirection);

        if ($query !== '') {
            $like = "%{$query}%";

            $qb->where('title', 'LIKE', $like);
        }

        foreach ($filters as $field => $value) {
            $qb->where($field, '=', $value);
        }

        $result = $qb->paginate($page, $perPage);

        $result['data'] = $qb->attachBelongsTo(
            $result['data'],
            'customers',
            'customer_id',
            'customer',
            params: ['id', 'name', 'company', 'company_email', 'email']
        );

        return $result;
    }
    public function create($data)
    {
        return $this->appointment->create($data);
    }

    public function update($id, $data)
    {
        return $this->appointment->update($id, $data);
    }

    public function findById($id)
    {
        return $this->appointment->find($id);
    }

    public function getlastsCustomers($customerId, $limit)
    {
        return $this->appointment->where('customer_id', '=', $customerId)->orderBy('created_at', 'DESC')->limit($limit)->get();
    }

    public function getAllByCustomer($customerId)
    {
        return $this->appointment->where('customer_id', '=', $customerId)->orderBy('created_at', 'DESC')->get();
    }
}