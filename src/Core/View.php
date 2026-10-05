<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    private static string $viewsDir = '';

    public static function setViewsDir(string $dir): void
    {
        self::$viewsDir = rtrim($dir, '/\\');
    }

    public static function render(string $template, array $data = [], ?string $layout = 'main'): string
    {
        if (self::$viewsDir === '') {
            self::$viewsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views';
        }

        $templateFile = self::$viewsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';
        if (!is_file($templateFile)) {
            throw new RuntimeException("View template not found: {$templateFile}");
        }

        // Render template within an isolated closure
        $content = self::evaluateFile($templateFile, $data);

        // If no layout requested, return content directly
        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::$viewsDir . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout view not found: {$layoutFile}");
        }

        $layoutData = array_merge($data, ['content' => $content]);
        return self::evaluateFile($layoutFile, $layoutData);
    }

    private static function evaluateFile(string $filePath, array $data): string
    {
        return (static function () use ($filePath, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            try {
                require $filePath;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string)ob_get_clean();
        })();
    }
}
