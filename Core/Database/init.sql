CREATE TABLE users (
    id CHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    email_verified_at TIMESTAMP NULL,
    verification_token VARCHAR(255) NULL,
    active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE tokens (
    id CHAR(255) PRIMARY KEY,
    user_id CHAR(255) NOT NULL,
    name VARCHAR(255) DEFAULT 'api-token',
    token_hash CHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE customers (
    id CHAR(36) NOT NULL,
    name VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    company VARCHAR(150) NOT NULL UNIQUE,
    company_role VARCHAR(100) NULL,
    company_email VARCHAR(150) NULL UNIQUE,
    address VARCHAR(255) NULL,
    location VARCHAR(500) NULL,
    cell_phone VARCHAR(30) NULL,
    landline_phone VARCHAR(30) NULL,
    social_links JSON NULL,
    website VARCHAR(150) NULL,
    state ENUM(
        'new',
        -- nuevo
        'contacted',
        -- contactado
        'interested',
        -- interesado
        'proposal_sent',
        -- propuesta_enviada
        'negotiating',
        -- negociando
        'closed' -- cerrado
    ) NOT NULL DEFAULT 'new',
    contacted_by VARCHAR(150) NULL,
    priority ENUM(
        'high',
        -- alta
        'medium',
        -- media
        'low' -- baja
    ) NOT NULL DEFAULT 'medium',
    project_type VARCHAR(150) NULL,
    -- o ENUM si tienes tipos fijos
    estimated_budget DECIMAL(12, 2) NULL,
    desired_timeframe VARCHAR(100) NULL,
    -- ej: "1 mes", "3-6 meses", "urgente"
    technology VARCHAR(255) NULL,
    -- o JSON si son varias
    competence VARCHAR(255) NULL,
    date_contact DATETIME NULL,
    description TEXT NULL,
    used_channel ENUM(
        'email',
        -- email
        'phone',
        -- telefono
        'whatsapp',
        -- whatsapp
        'linkedin',
        -- linkedin
        'web',
        -- web
        'meeting',
        -- reunion
        'referral',
        -- referido
        'other' -- otro
    ) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customers_email (email)
);

CREATE TABLE appointments (
    id CHAR(36) NOT NULL,
    customer_id CHAR(36) NOT NULL,
    title VARCHAR(200) NULL,
    -- ej: "Llamada inicial / Demo web"
    description TEXT NULL,
    -- contexto breve de la cita
    starts_at DATETIME NOT NULL,
    -- fecha y hora de inicio
    ends_at DATETIME NULL,
    -- fin (opcional)
    duration_minutes INT NULL,
    -- alternativa si no usas ends_at
    modality ENUM(
        'in_person',
        -- presencial
        'video_call',
        -- videollamada
        'phone',
        -- telefono
        'message',
        -- mensajes
        'other' -- otro
    ) NOT NULL DEFAULT 'video_call',
    location VARCHAR(500) NULL,
    -- dirección o link de Zoom/Meet
    meeting_url VARCHAR(500) NULL,
    -- link de la videollamada
    status ENUM(
        'scheduled',
        -- programada
        'confirmed',
        -- confirmada
        'completed',
        -- realizada
        'cancelled',
        -- cancelada
        'rescheduled',
        -- reprogramada
        'no_show' -- no_asistio
    ) NOT NULL DEFAULT 'scheduled',
    reminder_sent TINYINT(1) NOT NULL DEFAULT 0,
    -- si ya enviaste recordatorio
    reminder_at DATETIME NULL,
    -- cuándo enviar recordatorio
    notes TEXT NULL,
    -- notas rápidas previas a la reunión
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_appointments_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE
);

/* 
 CREATE TABLE meeting_notes (
 id CHAR(36) NOT NULL,
 appointment_id CHAR(36) NOT NULL,
 summary TEXT NULL,
 -- resumen de la reunión
 topics_discussed TEXT NULL,
 -- temas tratados
 client_needs TEXT NULL,
 -- necesidades/dolores del cliente
 agreed_items TEXT NULL,
 -- acuerdos alcanzados
 next_steps TEXT NULL,
 -- próximos pasos
 next_action_date DATETIME NULL,
 -- cuándo hacer el follow-up
 budget_discussed DECIMAL(12, 2) NULL,
 -- presupuesto hablado (si surge)
 proposal_sent TINYINT(1) NOT NULL DEFAULT 0,
 proposal_sent_at DATETIME NULL,
 client_sentiment ENUM(
 'very_positive',
 -- muy_positivo
 'positive',
 -- positivo
 'neutral',
 -- neutral
 'negative',
 -- negativo
 'very_negative' -- muy_negativo
 ) NULL,
 -- cómo salió el cliente
 duration_actual_min INT NULL,
 -- cuánto duró realmente
 attendees VARCHAR(255) NULL,
 -- quiénes estuvieron (texto libre o JSON)
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 UNIQUE KEY uq_meeting_notes_appointment (appointment_id),
 CONSTRAINT fk_meeting_notes_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE ON UPDATE CASCADE
 );
 
 CREATE TABLE deliverables (
 id CHAR(36) NOT NULL,
 customer_id CHAR(36) NOT NULL,
 appointment_id CHAR(36) NULL,
 -- si nació de una reunión (opcional)
 title VARCHAR(200) NOT NULL,
 -- ej: "Mockup home v1"
 description TEXT NULL,
 type ENUM(
 'demo',
 -- demo
 'mockup',
 -- mockup
 'prototype',
 -- prototipo
 'code_progress',
 -- avance_codigo
 'video',
 -- video
 'document',
 -- documento
 'other' -- otro
 ) NOT NULL DEFAULT 'demo',
 version VARCHAR(20) NULL,
 -- "v1", "v2", "v1.1" (útil si iteras)
 url VARCHAR(500) NULL,
 -- link a Figma, Vercel, staging, Drive...
 attachment_path VARCHAR(500) NULL,
 -- si subes archivo a tu servidor
 info_pdf_path VARCHAR(500) NULL,
 -- si subes pdf de info
 shared_channel ENUM(
 'email',
 -- email
 'whatsapp',
 -- whatsapp
 'linkedin',
 -- linkedin
 'meeting',
 -- reunion
 'web',
 -- web
 'other' -- otro
 ) NULL,
 -- por dónde se lo enviaste
 shared_at DATETIME NULL,
 -- cuándo se lo mostraste
 status ENUM(
 'draft',
 -- borrador
 'sent',
 -- enviado
 'under_review',
 -- en_revision
 'approved',
 -- aprobado
 'rejected',
 -- rechazado
 'changes_requested' -- cambios_solicitados
 ) NOT NULL DEFAULT 'draft',
 client_feedback TEXT NULL,
 -- qué dijo el cliente
 feedback_at DATETIME NULL,
 -- cuándo respondió
 approved_at DATETIME NULL,
 -- cuándo lo aprobó (si aplica)
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 CONSTRAINT fk_deliverables_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_deliverables_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE
 SET
 NULL ON UPDATE CASCADE
 );
 
 CREATE TABLE deliverable_files (
 id CHAR(36) NOT NULL,
 deliverable_id CHAR(36) NOT NULL,
 file_name VARCHAR(255) NOT NULL,
 file_path VARCHAR(500) NOT NULL,
 file_type VARCHAR(100) NULL,
 -- mime type
 file_size INT NULL,
 -- bytes
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 KEY idx_deliverable_files_deliverable (deliverable_id),
 CONSTRAINT fk_deliverable_files_deliverable FOREIGN KEY (deliverable_id) REFERENCES deliverables(id) ON DELETE CASCADE ON UPDATE CASCADE
 );
 
 CREATE TABLE projects (
 id CHAR(36) NOT NULL,
 customer_id CHAR(36) NOT NULL,
 name VARCHAR(200) NOT NULL,
 -- ej: "Web corporativa Pepito SL"
 description TEXT NULL,
 project_type VARCHAR(150) NULL,
 -- web, ecommerce, app, mantenimiento...
 technology VARCHAR(255) NULL,
 -- stack acordado
 status ENUM(
 'planned',
 -- planificado
 'in_progress',
 -- en_curso
 'on_hold',
 -- en_pausa
 'under_review',
 -- en_revision
 'completed',
 -- finalizado
 'cancelled' -- cancelado
 ) NOT NULL DEFAULT 'planned',
 start_date DATE NULL,
 -- cuándo empieza
 deadline DATE NULL,
 -- fecha comprometida de entrega
 delivered_at DATETIME NULL,
 -- cuándo se entregó realmente
 agreed_budget DECIMAL(12, 2) NULL,
 -- presupuesto cerrado (no estimado)
 amount_paid DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
 payment_status ENUM(
 'pending',
 -- pendiente
 'partial',
 -- parcial
 'paid',
 -- pagado
 'refunded' -- reembolsado
 ) NOT NULL DEFAULT 'pending',
 repository_url VARCHAR(500) NULL,
 -- GitHub/GitLab
 production_url VARCHAR(500) NULL,
 -- URL final en producción
 staging_url VARCHAR(500) NULL,
 -- entorno de pruebas
 notes TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 KEY idx_projects_customer (customer_id),
 KEY idx_projects_status (status),
 KEY idx_projects_deadline (deadline),
 CONSTRAINT fk_projects_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT ON UPDATE CASCADE
 ); */