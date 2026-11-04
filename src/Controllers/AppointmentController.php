<?php

namespace Src\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Support\Log;
use Core\Validation\ValidationException;

class AppointmentController
{
    public function __construct(Type $var = null)
    {
        $this->var = $var;
    }

    public function create(Request $req, Response $res)
    {
        try {
            $validated = $req->validate([
                'customer_id' => 'required|uuid',
                'title' => 'nullable|string|max:200',
                'description' => 'nullable|string',
                'starts_at' => 'required|date_format:Y-m-d\TH:i|after_or_equal_today',
                'ends_at' => 'nullable|date_format:Y-m-d\TH:i|gte:starts_at',
                'duration_minutes' => 'nullable|integer|min:1|max:1440',
                'modality' => 'required|in:in_person,video_call,phone,message,other',
                'location' => 'nullable|string',
                'meeting_url' => 'nullable|url',
                'status' => 'nullable|in:scheduled,confirmed,completed,cancelled,rescheduled,no_show',
                'reminder_sent' => 'nullable|boolean',
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
                'ends_at.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD HH:MM.',
                'ends_at.gte' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

                // duration_minutes
                'duration_minutes.integer' => 'La duración debe ser un número entero.',
                'duration_minutes.min' => 'La duración mínima es de 1 minuto.',
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

                // reminder_sent
                'reminder_sent.boolean' => 'El campo recordatorio enviado debe ser verdadero o falso.',

                // reminder_at
                'reminder_at.date_format' => 'La fecha del recordatorio debe tener el formato AAAA-MM-DD HH:MM.',

                // notes
                'notes.string' => 'Las notas deben ser texto.',
            ]);
        } catch (ValidationException $e) {
            Log::debug($e->getMessage());
            return $res->error($e->getMessage())->send();
        }
    }
}