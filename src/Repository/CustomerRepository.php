<?php

namespace Src\Repository;

use Src\Models\Customer;

class CustomerRepository
{
    public function __construct(private Customer $customer)
    {
        $this->customer = $customer;
    }

    public function all($page, $perPage)
    {
        return $this->customer->orderBy('created_at', 'DESC')->paginate($page, $perPage);
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