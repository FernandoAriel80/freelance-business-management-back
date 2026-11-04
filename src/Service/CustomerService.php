<?php

namespace Src\Service;

use Core\Security\Uuid;
use Core\Support\Date;
use Core\Support\Str;
use RuntimeException;
use Src\Repository\CustomerRepository;

class CustomerService
{
    public function __construct(private CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function createCustomer($data)
    {

        $existing = $this->customerRepository->findByEmail($data['email']);
        if ($existing)
            throw new RuntimeException('Error, el cliente ya existe con ese correo.');
        $payload = [
            'id' => Uuid::v4(),
            ...$data,
            'social_links' => $data['social_links'] != null ? Str::stringToJson($data['social_links']) : $data['social_links'],
        ];

        $created = $this->customerRepository->create($payload);
        if (!$created)
            throw new RuntimeException('Error al crear cliente.');

        return $created;

    }

    public function getOneCustomer($id)
    {

        $customer = $this->customerRepository->findById($id);
        if (!$customer)
            throw new RuntimeException('Error al conseguir el cliente.');

        $payload = [
            ...$customer,
            'social_links' => Str::jsonToString($customer['social_links']),
            'date_contact' => Date::format($customer['date_contact'], 'Y-m-d')

        ];

        return $payload;
    }
    public function getAll($currentPage)
    {
        //$currentPage = 1;
        $perPage = 8;

        $customers = $this->customerRepository->all($currentPage, $perPage);
        if (!$customers)
            throw new RuntimeException('Error al obtener los clientes.');
        $data = [];

        foreach ($customers['data'] as $value) {
            $data[] = [
                ...$value,

                'date_contact' => $value['date_contact'] != null ? Date::format($value['date_contact'], 'd/m/Y') : null,
                'created_at' => Date::toLocal($value['created_at']),
            ];
        }

        $meta = $customers['meta'];
        $payload = [
            'data' => $data,
            'meta' => $meta,
        ];
        return $payload;
    }



    public function updateCustomer($id, $data)
    {
        $existing = $this->customerRepository->findById($id);
        if (!$existing)
            throw new RuntimeException('Error, el clinete no existe.');

        $payload = [
            ...$data,
            'social_links' => $data['social_links'] != null ? Str::stringToJson($data['social_links']) : $data['social_links'],
        ];

        $updated = $this->customerRepository->update($existing['id'], $payload);
        if (!$updated)
            throw new RuntimeException('Error al actualziar  datos del cliente.');

        return $updated;
    }

    public function deleteCustomer($id)
    {
        $existing = $this->customerRepository->findById($id);
        if (!$existing)
            throw new RuntimeException('Error, el clinete no existe.');

        $deleted = $this->customerRepository->delete($existing['id']);
        if (!$deleted)
            throw new RuntimeException('Error al eliminar cliente');
        return $deleted;
    }


}