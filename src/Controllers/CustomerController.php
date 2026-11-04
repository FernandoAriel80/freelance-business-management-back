<?php

namespace Src\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Support\Log;
use Core\Validation\ValidationException;
use Src\Service\CustomerService;

class CustomerController
{
    public function __construct(private CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function create(Request $req, Response $res)
    {
        try {
            $validated = $req->validate([
                'name' => 'nullable|string|max:150',
                'email' => 'nullable|email|max:150',
                'company' => 'required|string|max:150',
                'company_rol' => 'nullable|string|max:100',
                'company_email' => 'nullable|email|max:150',
                'address' => 'nullable|string|max:255',
                'location' => 'nullable|url',
                'website' => 'nullable|url',
                'cell_phone' => 'nullable|string|max:30',
                'landline_phone' => 'nullable|string|max:30',
                'social_links' => 'nullable|string',
                'state' => 'required|in:new,contacted,interested,proposal_sent,negotiating,closed',
                'contacted_by' => 'nullable|string|max:150',
                'priority' => 'required|in:high,medium,low',
                'project_type' => 'nullable|string|max:150',
                'estimated_budget' => 'nullable|numeric|decimal',
                'desired_timeframe' => 'nullable|string|max:100',
                'technology' => 'nullable|string|max:255',
                'competence' => 'nullable|string|max:255',
                'date_contact' => 'nullable|date',
                'description' => 'nullable|string|max:500',
                'used_channel' => 'nullable|in:email,phone,whatsapp,linkedin,web,meeting,referral,other',
            ], [
                'name.required' => 'El nombre es obligatorio back.',
                'name.string' => 'El nombre tiene que ser texto.',
                'name.max' => 'El nombre no puede superar los 150 caracteres.',

                'email.string' => 'El email tiene que ser texto.',
                'email.max' => 'El email no puede superar los 150 caracteres.',

                'company.required' => 'La empresa es obligatoria.',
                'company.string' => 'La empresa tiene que ser texto.',
                'company.max' => 'La empresa no puede superar los 150 caracteres.',

                'company_rol.string' => 'El rol tiene que ser texto.',
                'company_rol.max' => 'El rol no puede superar los 100 caracteres.',

                'company_email.string' => 'El email de la empresa tiene que ser texto.',
                'company_email.max' => 'El email de la empresa no puede superar los 150 caracteres.',

                'address.string' => 'La dirección tiene que ser texto.',
                'address.max' => 'La dirección no puede superar los 255 caracteres.',

                'location.string' => 'La ubicación tiene que ser texto.',
                'location.max' => 'La ubicación no puede superar los 150 caracteres.',

                'cell_phone.string' => 'El celular tiene que ser texto.',
                'cell_phone.max' => 'El celular no puede superar los 30 caracteres.',

                'landline_phone.string' => 'El teléfono fijo tiene que ser texto.',
                'landline_phone.max' => 'El teléfono fijo no puede superar los 30 caracteres.',

                'social_liks.string' => 'Los links de redes tienen que ser texto.',

                'state.required' => 'El estado es obligatorio.',
                'state.in' => 'El estado no es válido.',

                'contacted_by.max' => 'Contactado por no puede superar los 150 caracteres.',

                'priority.required' => 'La prioridad es obligatoria.',
                'priority.in' => 'La prioridad no es válida.',

                'project_type.string' => 'El tipo de proyecto tiene que ser texto.',
                'project_type.max' => 'El tipo de proyecto no puede superar los 150 caracteres.',

                'estimated_budget.numeric' => 'El presupuesto estimado tiene que ser un número.',
                'estimated_budget.decimal' => 'El presupuesto estimado tiene que ser un decimal válido.',

                'desired_timeframe.string' => 'El plazo deseado tiene que ser texto.',
                'desired_timeframe.max' => 'El plazo deseado no puede superar los 100 caracteres.',

                'technology.string' => 'La tecnología tiene que ser texto.',
                'technology.max' => 'La tecnología no puede superar los 255 caracteres.',

                'competence.string' => 'La competencia tiene que ser texto.',
                'competence.max' => 'La competencia no puede superar los 255 caracteres.',

                'date_contact.date' => 'La fecha de contacto tiene que ser una fecha válida.',

                'description.string' => 'La descripción tiene que ser texto.',
                'description.max' => 'La descripción no puede superar los 500 caracteres.',

                'used_channel.in' => 'El canal usado no es válido.',
            ]);

            $this->customerService->createCustomer($validated);
            return $res->success(['message' => 'El cliente a sido creado exitosamente.', 201])->send();
        } catch (ValidationException $e) {
            Log::debug($e->errors);
            return $res->json(['errors' => $e->errors], 422)->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage(), 409)->send();
        }
    }

    public function show(Request $req, Response $res)
    {
        try {
            $id = $req->param('id');

            $customer = $this->customerService->getOneCustomer($id);

            return $res->success($customer)->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

    public function all(Request $req, Response $res)
    {
        try {
            $currentPage = $req->has('page') ? $req->param('page') : 1;
            $customers = $this->customerService->getAll($currentPage);

            return $res->success($customers)->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage(), 409)->send();
        }
    }

    public function update(Request $req, Response $res)
    {

        try {
            $id = $req->param('id');

            $validated = $req->validate([
                'name' => 'nullable|string|max:150',
                'email' => 'nullable|email|max:150',
                'company' => 'required|string|max:150',
                'company_rol' => 'nullable|string|max:100',
                'company_email' => 'nullable|email|max:150',
                'address' => 'nullable|string|max:255',
                'location' => 'nullable|url',
                'website' => 'nullable|url',
                'cell_phone' => 'nullable|string|max:30',
                'landline_phone' => 'nullable|string|max:30',
                'social_links' => 'nullable|string',
                'state' => 'required|in:new,contacted,interested,proposal_sent,negotiating,closed',
                'contacted_by' => 'nullable|string|max:150',
                'priority' => 'required|in:high,medium,low',
                'project_type' => 'nullable|string|max:150',
                'estimated_budget' => 'nullable|numeric|decimal',
                'desired_timeframe' => 'nullable|string|max:100',
                'technology' => 'nullable|string|max:255',
                'competence' => 'nullable|string|max:255',
                'date_contact' => 'nullable|date',
                'description' => 'nullable|string|max:500',
                'used_channel' => 'nullable|in:email,phone,whatsapp,linkedin,web,meeting,referral,other',
            ], [
                'name.required' => 'El nombre es obligatorio back.',
                'name.string' => 'El nombre tiene que ser texto.',
                'name.max' => 'El nombre no puede superar los 150 caracteres.',

                'email.string' => 'El email tiene que ser texto.',
                'email.max' => 'El email no puede superar los 150 caracteres.',

                'company.required' => 'La empresa es obligatoria.',
                'company.string' => 'La empresa tiene que ser texto.',
                'company.max' => 'La empresa no puede superar los 150 caracteres.',

                'company_rol.string' => 'El rol tiene que ser texto.',
                'company_rol.max' => 'El rol no puede superar los 100 caracteres.',

                'company_email.string' => 'El email de la empresa tiene que ser texto.',
                'company_email.max' => 'El email de la empresa no puede superar los 150 caracteres.',

                'address.string' => 'La dirección tiene que ser texto.',
                'address.max' => 'La dirección no puede superar los 255 caracteres.',

                'location.string' => 'La ubicación tiene que ser texto.',
                'location.max' => 'La ubicación no puede superar los 150 caracteres.',

                'cell_phone.string' => 'El celular tiene que ser texto.',
                'cell_phone.max' => 'El celular no puede superar los 30 caracteres.',

                'landline_phone.string' => 'El teléfono fijo tiene que ser texto.',
                'landline_phone.max' => 'El teléfono fijo no puede superar los 30 caracteres.',

                'social_liks.string' => 'Los links de redes tienen que ser texto.',

                'state.required' => 'El estado es obligatorio.',
                'state.in' => 'El estado no es válido.',

                'contacted_by.max' => 'Contactado por no puede superar los 150 caracteres.',

                'priority.required' => 'La prioridad es obligatoria.',
                'priority.in' => 'La prioridad no es válida.',

                'project_type.string' => 'El tipo de proyecto tiene que ser texto.',
                'project_type.max' => 'El tipo de proyecto no puede superar los 150 caracteres.',

                'estimated_budget.numeric' => 'El presupuesto estimado tiene que ser un número.',
                'estimated_budget.decimal' => 'El presupuesto estimado tiene que ser un decimal válido.',

                'desired_timeframe.string' => 'El plazo deseado tiene que ser texto.',
                'desired_timeframe.max' => 'El plazo deseado no puede superar los 100 caracteres.',

                'technology.string' => 'La tecnología tiene que ser texto.',
                'technology.max' => 'La tecnología no puede superar los 255 caracteres.',

                'competence.string' => 'La competencia tiene que ser texto.',
                'competence.max' => 'La competencia no puede superar los 255 caracteres.',

                'date_contact.date' => 'La fecha de contacto tiene que ser una fecha válida.',

                'description.string' => 'La descripción tiene que ser texto.',
                'description.max' => 'La descripción no puede superar los 500 caracteres.',

                'used_channel.in' => 'El canal usado no es válido.',
            ]);

            $this->customerService->updateCustomer($id, $validated);

            return $res->success(['message' => 'El producto a sido actualizado exitosamente'])->send();
        } catch (ValidationException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

    public function delete(Request $req, Response $res)
    {
        try {
            $id = $req->param('id');
            $this->customerService->deleteCustomer($id);

            return $res->success(['message' => 'El producto a sido eliminado exitosamente'])->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage(), 409)->send();
        }
    }
}