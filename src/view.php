<?php
declare(strict_types=1);

/** HTML-escape for element content and quoted attributes. */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Render a template from /templates with an isolated variable scope. */
function render(string $template, array $vars = []): void
{
    $file = __DIR__ . '/../templates/' . basename($template) . '.php';
    (static function (string $__file, array $__vars): void {
        extract($__vars, EXTR_SKIP);
        require $__file;
    })($file, $vars);
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}
