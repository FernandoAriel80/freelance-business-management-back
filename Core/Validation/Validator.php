<?php
namespace Core\Validation;

class Validator
{
    private array $data;
    private array $rules;
    private array $customMessages;
    private array $errors = [];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /*     public function validate(): array
        {
            foreach ($this->rules as $field => $ruleSet) {
                $rules = explode('|', $ruleSet);
                $value = $this->data[$field] ?? null;
                $isRequired = in_array('required', $rules);
                $isNullable = in_array('nullable', $rules);
                $isPresent = in_array('present', $rules);

                // 'present': el campo debe existir, aunque sea null/vacío
                if ($isPresent && !array_key_exists($field, $this->data)) {
                    $this->addError($field, 'present', 'debe estar presente', []);
                    continue;
                }

                // 'nullable': si es null, se saltan el resto de reglas (salvo present, ya chequeado)
                if ($value === null && $isNullable) {
                    continue;
                }

                // Si no es required y está vacío, no validamos más (comportamiento original)
                if (!$isRequired && ($value === null || $value === '')) {
                    continue;
                }

                foreach ($rules as $rule) {
                    $this->applyRule($field, $value, $rule);
                }
            }

            if (!empty($this->errors)) {
                throw new ValidationException($this->errors);
            }

            return array_intersect_key($this->data, $this->rules);
        } */

    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;
            $isRequired = in_array('required', $rules);
            $isNullable = in_array('nullable', $rules);
            $isPresent = in_array('present', $rules);

            if ($isPresent && !array_key_exists($field, $this->data)) {
                $this->addError($field, 'present', 'debe estar presente', []);
                continue;
            }

            if ($value === null && $isNullable) {
                continue;
            }

            if (!$isRequired && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        $validated = array_intersect_key($this->data, $this->rules);

        // Normalizar: "" → null
        return array_map(
            fn($v) => is_string($v) && $v === '' ? null : $v,
            $validated
        );
    }

    private function applyRule(string $field, $value, string $rule): void
    {
        $params = explode(':', $rule, 2);
        $name = $params[0];
        $param = $params[1] ?? null;

        switch ($name) {
            // ─────────────────────────────────────────────
            // Básicas
            // ─────────────────────────────────────────────
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, 'required', 'es requerido', []);
                }
                break;

            case 'nullable':
            case 'present':
                // Ya manejadas en validate()
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, 'string', 'debe ser texto', []);
                }
                break;

            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'integer', 'debe ser entero', []);
                }
                break;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, 'numeric', 'debe ser numérico', []);
                }
                break;

            case 'decimal':
                if ($value !== null && !preg_match('/^-?\d+(\.\d+)?$/', (string) $value)) {
                    $this->addError($field, 'decimal', 'debe ser un decimal válido', []);
                }
                break;

            case 'boolean':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    $this->addError($field, 'boolean', 'debe ser booleano', []);
                }
                break;

            case 'email':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email', 'email inválido', []);
                }
                break;

            case 'url':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url', 'debe ser una URL válida', []);
                }
                break;

            case 'ip':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addError($field, 'ip', 'debe ser una IP válida', []);
                }
                break;

            // ─────────────────────────────────────────────
            // Cadenas
            // ─────────────────────────────────────────────
            case 'min':
                if (is_string($value) && mb_strlen($value) < (int) $param) {
                    $this->addError($field, 'min', 'mínimo :param caracteres', ['param' => $param]);
                }
                if (is_numeric($value) && $value < (int) $param) {
                    $this->addError($field, 'min', 'mínimo :param', ['param' => $param]);
                }
                break;

            case 'max':
                if (is_string($value) && mb_strlen($value) > (int) $param) {
                    $this->addError($field, 'max', 'máximo :param caracteres', ['param' => $param]);
                }
                if (is_numeric($value) && $value > (int) $param) {
                    $this->addError($field, 'max', 'máximo :param', ['param' => $param]);
                }
                break;

            case 'alpha':
                if ($value !== null && !preg_match('/^[\pL]+$/u', (string) $value)) {
                    $this->addError($field, 'alpha', 'solo debe contener letras', []);
                }
                break;

            case 'alpha_num':
                if ($value !== null && !preg_match('/^[\pL\pN]+$/u', (string) $value)) {
                    $this->addError($field, 'alpha_num', 'solo debe contener letras y números', []);
                }
                break;

            case 'starts_with':
                if ($value !== null) {
                    $prefijos = explode(',', $param);
                    $ok = false;
                    foreach ($prefijos as $p) {
                        if (str_starts_with((string) $value, $p)) {
                            $ok = true;
                            break;
                        }
                    }
                    if (!$ok) {
                        $this->addError($field, 'starts_with', 'debe comenzar con :param', ['param' => $param]);
                    }
                }
                break;

            case 'ends_with':
                if ($value !== null) {
                    $sufijos = explode(',', $param);
                    $ok = false;
                    foreach ($sufijos as $s) {
                        if (str_ends_with((string) $value, $s)) {
                            $ok = true;
                            break;
                        }
                    }
                    if (!$ok) {
                        $this->addError($field, 'ends_with', 'debe terminar con :param', ['param' => $param]);
                    }
                }
                break;

            case 'regex':
                if ($value !== null && !preg_match($param, (string) $value)) {
                    $this->addError($field, 'regex', 'el formato no es válido', []);
                }
                break;

            case 'uuid':
                if ($value !== null && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value)) {
                    $this->addError($field, 'uuid', 'debe ser un UUID válido', []);
                }
                break;

            case 'json':
                if ($value !== null) {
                    json_decode((string) $value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->addError($field, 'json', 'debe ser un JSON válido', []);
                    }
                }
                break;

            case 'digits':
                if ($value !== null && !preg_match('/^\d{' . (int) $param . '}$/', (string) $value)) {
                    $this->addError($field, 'digits', 'debe tener :param dígitos', ['param' => $param]);
                }
                break;

            case 'digits_between':
                $range = explode(',', $param);
                $min = (int) ($range[0] ?? 0);
                $max = (int) ($range[1] ?? PHP_INT_MAX);
                if ($value !== null && !preg_match('/^\d+$/', (string) $value)) {
                    $this->addError($field, 'digits_between', 'debe contener solo dígitos', []);
                } elseif ($value !== null) {
                    $len = strlen((string) $value);
                    if ($len < $min || $len > $max) {
                        $this->addError($field, 'digits_between', 'debe tener entre :min y :max dígitos', ['min' => $min, 'max' => $max]);
                    }
                }
                break;

            case 'in':
                if ($value !== null && !in_array($value, explode(',', $param))) {
                    $this->addError($field, 'in', 'valor no permitido', []);
                }
                break;

            case 'not_in':
                if ($value !== null && in_array($value, explode(',', $param))) {
                    $this->addError($field, 'not_in', 'valor no permitido', []);
                }
                break;

            case 'same':
                if ($value !== ($this->data[$param] ?? null)) {
                    $this->addError($field, 'same', 'debe coincidir con :param', ['param' => $param]);
                }
                break;

            case 'different':
                if ($value === ($this->data[$param] ?? null)) {
                    $this->addError($field, 'different', 'debe ser diferente de :param', ['param' => $param]);
                }
                break;

            case 'confirmed':
                if ($value !== ($this->data[$field . '_confirmation'] ?? null)) {
                    $this->addError($field, 'confirmed', 'la confirmación no coincide', []);
                }
                break;

            // ─────────────────────────────────────────────
            // Fechas
            // ─────────────────────────────────────────────
            case 'date':
                if ($value !== null && strtotime((string) $value) === false) {
                    $this->addError($field, 'date', 'debe ser una fecha válida', []);
                }
                break;

            case 'date_format':
                if ($value !== null) {
                    $d = \DateTime::createFromFormat($param, (string) $value);
                    if (!$d || $d->format($param) !== (string) $value) {
                        $this->addError($field, 'date_format', 'debe tener el formato :param', ['param' => $param]);
                    }
                }
                break;

            case 'after_or_equal_today':
                if ($value !== null) {
                    // Toma solo la parte de fecha (funciona con "Y-m-d" y con "Y-m-d\TH:i")
                    $fechaInput = substr((string) $value, 0, 10);
                    $hoy = date('Y-m-d');

                    // Comparación lexicográfica: "YYYY-MM-DD" ordena igual que cronológicamente
                    if ($fechaInput < $hoy) {
                        $this->addError($field, 'after_or_equal_today', 'no puede ser una fecha anterior a hoy', []);
                    }
                }
                break;

            case 'gte':
                if ($value !== null && isset($this->data[$param]) && $value < $this->data[$param]) {
                    $this->addError($field, 'gte', 'debe ser mayor o igual que :param', ['param' => $param]);
                }
                break;

            case 'lte':
                if ($value !== null && isset($this->data[$param]) && $value > $this->data[$param]) {
                    $this->addError($field, 'lte', 'debe ser menor o igual que :param', ['param' => $param]);
                }
                break;

            // ─────────────────────────────────────────────
            // Arrays
            // ─────────────────────────────────────────────
            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->addError($field, 'array', 'debe ser un array', []);
                }
                break;

            case 'array_min':
                if (is_array($value) && count($value) < (int) $param) {
                    $this->addError($field, 'array_min', 'debe tener al menos :param elementos', ['param' => $param]);
                }
                break;

            case 'array_max':
                if (is_array($value) && count($value) > (int) $param) {
                    $this->addError($field, 'array_max', 'no puede tener más de :param elementos', ['param' => $param]);
                }
                break;

            // ─────────────────────────────────────────────
            // Archivos
            // ─────────────────────────────────────────────
            case 'file':
                if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                    $this->addError($field, 'file', 'archivo inválido', []);
                }
                break;

            case 'image':
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    // Validación real con finfo (no confiar en $_FILES['type'])
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($_FILES[$field]['tmp_name']);
                    if (!str_starts_with($mime, 'image/')) {
                        $this->addError($field, 'image', 'debe ser una imagen', []);
                    }
                }
                break;

            case 'mimes':
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $allowed = array_map('strtolower', explode(',', $param));
                    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed, true)) {
                        $this->addError($field, 'mimes', 'debe ser un archivo de tipo: :param', ['param' => $param]);
                    }
                }
                break;

            case 'mimetypes':
                // A diferencia de 'mimes' (extensión), valida el MIME real del contenido
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $allowed = array_map('trim', explode(',', $param));
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($_FILES[$field]['tmp_name']);
                    if (!in_array($mime, $allowed, true)) {
                        $this->addError($field, 'mimetypes', 'el tipo de archivo no es válido', []);
                    }
                }
                break;

            case 'max_size':
                // param en KB
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $maxBytes = (int) $param * 1024;
                    if ($_FILES[$field]['size'] > $maxBytes) {
                        $this->addError($field, 'max_size', 'no puede superar :param KB', ['param' => $param]);
                    }
                }
                break;

            case 'dimensions':
                // Sintaxis: dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $info = @getimagesize($_FILES[$field]['tmp_name']);
                    if ($info === false) {
                        $this->addError($field, 'dimensions', 'no es una imagen válida', []);
                    } else {
                        [$width, $height] = $info;
                        foreach (explode(',', $param) as $constraint) {
                            [$key, $val] = explode('=', $constraint);
                            $val = (int) $val;
                            if ($key === 'min_width' && $width < $val)
                                $this->addError($field, 'dimensions', "ancho mínimo :param px", ['param' => $val]);
                            if ($key === 'max_width' && $width > $val)
                                $this->addError($field, 'dimensions', "ancho máximo :param px", ['param' => $val]);
                            if ($key === 'min_height' && $height < $val)
                                $this->addError($field, 'dimensions', "alto mínimo :param px", ['param' => $val]);
                            if ($key === 'max_height' && $height > $val)
                                $this->addError($field, 'dimensions', "alto máximo :param px", ['param' => $val]);
                        }
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $ruleName, string $defaultMsg, array $placeholders): void
    {
        $customKey = "{$field}.{$ruleName}";

        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        } else {
            $message = $defaultMsg;
            foreach ($placeholders as $key => $val) {
                $message = str_replace(":{$key}", (string) $val, $message);
            }
            $message = ucfirst($field) . ": {$message}";
        }

        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
/* 

$request->validate([
   'name'         => 'required|string|max:80',
   'email'        => 'required|email',
   'age'          => 'nullable|integer|min:18',
   'price'        => 'required|numeric|decimal',
   'birth_date'   => 'required|date_format:Y-m-d',
   'end_date'     => 'required|date|gte:start_date',
   'password'     => 'required|min:8|confirmed',
   'phone'        => 'nullable|regex:/^\+?[0-9]{9,15}$/',
   'role'         => 'required|in:admin,user,editor',
   'slug'         => 'required|alpha_num|uuid',
   'tags'         => 'nullable|array|array_min:1|array_max:5',
   'website'      => 'nullable|url',
    'estado'      => 'required|in:activo,inactivo,suspendido',
    'email' => 'required|email|not_in:test@test.com,temp@temp.com',
   'ip_address'   => 'nullable|ip',
   'metadata'     => 'nullable|json',
   'avatar'       => 'nullable|image|mimes:jpg,png,webp|max_size:2048|dimensions:min_width=100,min_height=100',
   'document'     => 'required|file|mimes:pdf,docx|max_size:5120',
   'date_contact' => 'nullable|date_format:Y-m-d|after_or_equal_today',
   'date_contact' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal_today',
], [
   'name.required'      => 'El nombre es obligatorio.',
   'password.confirmed' => 'Las contraseñas no coinciden.',
   'avatar.image'       => 'El avatar debe ser una imagen.',
]);*/