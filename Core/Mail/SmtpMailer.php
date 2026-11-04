<?php
namespace Core\Mail;

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private bool $useTls;

    public function __construct(string $host, int $port, string $username, string $password, bool $useTls = true)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->useTls =  filter_var($useTls, FILTER_VALIDATE_BOOLEAN);
    }

    public function send(string $from, string $to, string $subject, string $htmlBody): bool
    {
        // 1. Abrir conexión
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("SMTP Error: $errstr ($errno)");
            return false;
        }

        // 2. Leer la respuesta de bienvenida (220)
        $this->readResponse($socket);

        // 3. Identificarse (EHLO)
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $this->readResponse($socket);

        // --- NUEVO: Iniciar cifrado TLS si es necesario ---
        if ($this->useTls) {
            // 1. Pedir STARTTLS
            fwrite($socket, "STARTTLS\r\n");
            $response = $this->readResponse($socket);

            // Verificar que el servidor aceptó (código 220)
            if (!str_starts_with($response, '220')) {
                error_log("SMTP Error: El servidor no aceptó STARTTLS. Respuesta: $response");
                fclose($socket);
                return false;
            }

            // 2. Activar el cifrado en el socket
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                error_log("SMTP Error: No se pudo habilitar el cifrado TLS.");
                fclose($socket);
                return false;
            }
        }
        // --- FIN NUEVO ---

        // 4. Autenticación (AUTH LOGIN)
        if ($this->username && $this->password) {
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->readResponse($socket);
            fwrite($socket, base64_encode($this->username) . "\r\n");
            $this->readResponse($socket);
            fwrite($socket, base64_encode($this->password) . "\r\n");
            $this->readResponse($socket);
        }

        // 5. Establecer remitente y destinatario
        fwrite($socket, "MAIL FROM: <$from>\r\n");
        $this->readResponse($socket);
        fwrite($socket, "RCPT TO: <$to>\r\n");
        $this->readResponse($socket);

        // 6. Enviar datos (DATA)
        fwrite($socket, "DATA\r\n");
        $this->readResponse($socket);

        // 7. Construir el cuerpo del mensaje (Headers + Body)
        $message = "Subject: $subject\r\n";
        $message .= "To: <$to>\r\n";
        $message .= "From: Tu App <$from>\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n"; // Separador entre headers y body
        $message .= $htmlBody . "\r\n";

        // 8. Enviar mensaje y terminarlo con un punto en una línea
        fwrite($socket, $message . ".\r\n");
        $response = $this->readResponse($socket);

        // 9. Cerrar conexión
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        // Verificar si el servidor aceptó el mensaje (código 250)
        return str_starts_with($response, '250');
    }

    private function readResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }
}

/* 
Para Brevo (puerto 587): new SmtpMailer('smtp-relay.brevo.com', 587, 'tu_usuario', 'tu_smtp_key', true);

Para Mailpit (sin TLS): new SmtpMailer('127.0.0.1', 1025, '', '', false);

 */

/* 


    $htmlBody = View::render(__DIR__ . '/../../Views/emails/welcome.php', [
        'nombre' => $data['name'] ?? 'Usuario',
        'enlace' => "https://tudominio.com/api/auth/verify?token={$token}",
    ]);

    $mailer = new SmtpMailer(
        getenv('MAIL_HOST'),
        (int) getenv('MAIL_PORT'),
        getenv('MAIL_USER'),
        getenv('MAIL_PASS'),
        filter_var(getenv('MAIL_TLS'), FILTER_VALIDATE_BOOLEAN)
    );
    
    $mailer->send("no-reply@tudominio.com",$data['email'], $htmlBody);
    */