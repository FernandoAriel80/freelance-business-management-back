<?php

namespace Src\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Support\Log;
use Core\Validation\ValidationException;
use Src\Service\AppointmentService;

class AppointmentController
{
    public function __construct(private AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function create(Request $req, Response $res)
    {
        try {
            $validated = $req->validate([
                'customer_id' => 'required|uuid',
                'title' => 'nullable|string|max:200',
                'description' => 'nullable|string',
                'starts_at' => 'required|date_format:Y-m-d\TH:i|after_or_equal_today',
                'ends_at' => 'nullable|date_format:H:i',
                'duration_minutes' => 'nullable|integer|max:1440',
                'modality' => 'required|in:in_person,video_call,phone,message,other',
                'location' => 'nullable|string',
                'meeting_url' => 'nullable|url',
                'status' => 'nullable|in:scheduled,confirmed,completed,cancelled,rescheduled,no_show',
                'reminder_at' => 'nullable|date_format:Y-m-d\TH:i',
                'notes' => 'nullable|string',
            ], [
                // customer_id
                'customer_id.required' => 'El cliente es obligatorio.',
                'customer_id.uuid' => 'El identificador del cliente no es válido.',

                // title
                'title.string' => 'El título debe ser texto.',
                'title.max' => 'El título no puede superar los 200 caracteres.',

                // description
                'description.string' => 'La descripción debe ser texto.',

                // starts_at
                'starts_at.required' => 'La fecha y hora de inicio es obligatoria.',
                'starts_at.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD HH:MM.',
                'starts_at.after_or_equal_today' => 'La fecha de inicio no puede ser anterior a hoy.',

                // ends_at
                'ends_at.date_format' => 'La hora de fin debe tener el formato HH:MM.',

                // duration_minutes
                'duration_minutes.integer' => 'La duración debe ser un número entero.',
               // 'duration_minutes.min' => 'La duración mínima es de 1 minuto.',
                'duration_minutes.max' => 'La duración no puede superar los 1440 minutos (24 horas).',

                // modality
                'modality.required' => 'La modalidad es obligatoria.',
                'modality.in' => 'La modalidad debe ser: presencial, videollamada, teléfono, mensaje u otro.',

                // location
                'location.string' => 'La ubicación debe ser texto.',
                //'location.max' => 'La ubicación no puede superar los 255 caracteres.',

                // meeting_url
                'meeting_url.url' => 'El enlace de la reunión debe ser una URL válida.',
                //'meeting_url.max' => 'El enlace de la reunión no puede superar los 255 caracteres.',

                // status
                'status.in' => 'El estado debe ser: programada, confirmada, realizada, cancelada, reprogramada o no asistió.',

                // reminder_at
                'reminder_at.date_format' => 'La fecha del recordatorio debe tener el formato AAAA-MM-DD HH:MM.',

                // notes
                'notes.string' => 'Las notas deben ser texto.',
            ]);

            $this->appointmentService->createAppointment($validated);

            return $res->success(['message' => 'La cita a sido creado exitosamente.'], 201)->send();
        } catch (ValidationException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

    public function getLastByCustomer(Request $req, Response $res)
    {
        try {
            $customerId = $req->param('customerId');
            $customers = $this->appointmentService->getLastsCustomer($customerId);
            return $res->success($customers)->send();
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

    public function getAllByCustomer(Request $req, Response $res)
    {
        try {
            $customerId = $req->param('customerId');
            $customers = $this->appointmentService->getAllByCustomer($customerId);
            return $res->success($customers)->send();
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

    public function all(Request $req, Response $res)
    {
        try {
            $currentPage = $req->has('page') ? $req->param('page') : 1;
            $query = $query = $req->input('q') ?? '';

            $allowedFilters = [
                'status' => ['scheduled', 'confirmed', 'completed', 'cancelled', 'rescheduled', 'no_show'], // tus valores reales
                'modality' => ['in_person', 'video_call', 'phone', 'message', 'other'],
            ];

            $filters = [];
            foreach ($allowedFilters as $field => $allowedValues) {
                if (!empty($req->input($field)) && in_array($req->input($field), $allowedValues, true)) {
                    $filters[$field] = $req->input($field);
                }
            }

            // ordenamiento (whitelist columna+dirección)
            $allowedSorts = [
                'created_at_desc' => ['created_at', 'DESC'],
                'created_at_asc' => ['created_at', 'ASC'],
                'updated_at_desc' => ['updated_at', 'DESC'],
                'updated_at_at_asc' => ['updated_at', 'ASC'],
                'title_asc' => ['title', 'ASC'],
                'title_desc' => ['title', 'DESC'],
                'starts_at_desc' => ['starts_at', 'DESC'],
                'starts_at_asc' => ['starts_at', 'ASC']
            ];
            $sort = $allowedSorts[$req->input('sort') ?? ''] ?? ['created_at', 'DESC'];

            $appointments = $this->appointmentService->getAll($currentPage, $query, $filters, $sort);

            return $res->success($appointments)->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

    public function update(Request $req, Response $res)
    {
        try {

            $id = $req->param('id');

            $validated = $req->validate([
                'customer_id' => 'required|uuid',
                'title' => 'nullable|string|max:200',
                'description' => 'nullable|string',
                'starts_at' => 'required',
                'ends_at' => 'nullable',
                'duration_minutes' => 'nullable|integer|max:1440',
                'modality' => 'required|in:in_person,video_call,phone,message,other',
                'location' => 'nullable|string',
                'meeting_url' => 'nullable|url',
                'status' => 'nullable|in:scheduled,confirmed,completed,cancelled,rescheduled,no_show',
                'reminder_at' => 'nullable',
                'notes' => 'nullable|string',
            ], [
                // customer_id
                'customer_id.required' => 'El cliente es obligatorio.',
                'customer_id.uuid' => 'El identificador del cliente no es válido.',

                // title
                'title.string' => 'El título debe ser texto.',
                'title.max' => 'El título no puede superar los 200 caracteres.',

                // description
                'description.string' => 'La descripción debe ser texto.',

                // starts_at
                'starts_at.required' => 'La fecha y hora de inicio es obligatoria.',
                // 'starts_at.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD HH:MM.',
                //'starts_at.after_or_equal_today' => 'La fecha de inicio no puede ser anterior a hoy.',

                // ends_at
                // 'ends_at.date_format' => 'La hora de fin debe tener el formato HH:MM.',

                // duration_minutes
                'duration_minutes.integer' => 'La duración debe ser un número entero.',
                //'duration_minutes.min' => 'La duración mínima es de 1 minuto.',
                'duration_minutes.max' => 'La duración no puede superar los 1440 minutos (24 horas).',

                // modality
                'modality.required' => 'La modalidad es obligatoria.',
                'modality.in' => 'La modalidad debe ser: presencial, videollamada, teléfono, mensaje u otro.',

                // location
                'location.string' => 'La ubicación debe ser texto.',
                //'location.max' => 'La ubicación no puede superar los 255 caracteres.',

                // meeting_url
                'meeting_url.url' => 'El enlace de la reunión debe ser una URL válida.',
                //'meeting_url.max' => 'El enlace de la reunión no puede superar los 255 caracteres.',

                // status
                'status.in' => 'El estado debe ser: programada, confirmada, realizada, cancelada, reprogramada o no asistió.',

                // reminder_at
                // 'reminder_at.date_format' => 'La fecha del recordatorio debe tener el formato AAAA-MM-DD HH:MM.',

                // notes
                'notes.string' => 'Las notas deben ser texto.',
            ]);

            $this->appointmentService->updateAppointment($id, $validated);

            return $res->success(['message' => 'La reunión a sido creado exitosamente.'], 201)->send();
        } catch (ValidationException $e) {
            Log::debug($e->getErrors());
            return $res->error($e->getMessage())->send();
        } catch (\RuntimeException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }

}