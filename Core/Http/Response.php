<?php
namespace Core\Http;
class Response
{
    private $content = '';
    private $statusCode = 200;
    private $headers = [];
    private $sent = false;

    public function message(string $message, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->setJsonHeader();
        $this->content = json_encode(['message' => $message]);
        return $this;
    }

    public function success(array $data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->setJsonHeader();
        $this->content = json_encode($data);
        return $this;
    }

    public function error(string $message, int $status = 500): self
    {
        $this->statusCode = $status;
        $this->setJsonHeader();
        $this->content = json_encode(['error' => $message]);
        return $this;
    }

    public function notFound(string $message, int $status = 404): self
    {
        $this->statusCode = $status;
        $this->setJsonHeader();
        $this->content = json_encode(['error' => $message]);
        return $this;
    }

    public function json($data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->setJsonHeader();
        $this->content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    private function setJsonHeader(): void
    {
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
    }

    public function send(): self
    {
        if ($this->sent) {
            return $this;
        }

        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        echo $this->content;
        $this->sent = true;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }
}