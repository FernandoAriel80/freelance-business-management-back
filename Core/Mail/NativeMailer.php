<?php
namespace Core\Mail;

class NativeMailer
{
    public function __construct(
        private string $fromEmail = 'no-reply@tudominio.com',
        private string $fromName  = 'Tu App',
        private ?string $replyTo  = null
    ) {}

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            "From: {$this->fromName} <{$this->fromEmail}>",
            'X-Mailer: PHP/' . phpversion(),
        ];

        if ($this->replyTo) {
            $headers[] = "Reply-To: {$this->replyTo}";
        }

        // Asunto codificado para caracteres especiales (ñ, acentos, etc.)
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        return mail(
            $to,
            $encodedSubject,
            $htmlBody,
            implode("\r\n", $headers)
        );
    }
}

/*
    $htmlBody = View::render(__DIR__ . '/../../Views/emails/welcome.php', [
        'nombre' => $data['name'] ?? 'Usuario',
        'enlace' => "https://tudominio.com/api/auth/verify?token={$token}",
    ]);

    
$mailer = new NativeMailer(
    fromEmail: 'no-reply@tudominio.com',
    fromName:  'Mi App',
    replyTo:   'soporte@tudominio.com'
);

$mailer->send($data['email'], $htmlBody); */
