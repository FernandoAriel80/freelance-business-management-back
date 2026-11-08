<?php

namespace Src\Repository;

use Src\Models\Customer;

class CustomerRepository
{
    public function __construct(private Customer $customer)
    {
        $this->customer = $customer;
    }

    public function all($page, $perPage, $query = '', array $filters = [], array $sort = ['created_at', 'DESC'])
    {
        [$sortColumn, $sortDirection] = $sort;
        $qb = $this->customer->orderBy($sortColumn, $sortDirection);

        if ($query !== '') {
            $like = "%{$query}%";

            $qb->whereGroup(function ($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                    ->orWhere('email', 'LIKE', $like)
                    ->orWhere('company', 'LIKE', $like)
                    ->orWhere('cell_phone', 'LIKE', $like)
                    ->orWhere('landline_phone', 'LIKE', $like);
            });
        }

        foreach ($filters as $field => $value) {
            $qb->where($field, '=', $value);
        }

        return $qb->paginate($page, $perPage);
    }

    public function findById($id)
    {
        return $this->customer->find($id);
    }

    public function findByEmail($email)
    {
        return $this->customer->where('email', '=', $email)->first();
    }

    public function create($data)
    {
        return $this->customer->create($data);
    }

    public function update($id, $data)
    {
        return $this->customer->update($id, $data);
    }

    public function filter($data)
    {

    }

    public function delete($id)
    {
        return $this->customer->delete($id);
    }
}