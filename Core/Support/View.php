<?php 

namespace Core\Support;
class View
{
    public static function render(string $templatePath, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}