<?php
namespace Core\Validation;

class ValidationException extends \Exception
{
    public array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Error de validación');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}