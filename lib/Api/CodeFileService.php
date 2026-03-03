<?php

namespace FriendsOfRedaxo\Code\Api;

use FriendsOfRedaxo\Code\EditorConfig;
use rex_dir;
use rex_file;
use rex_path;

final class CodeFileService
{
    private array $protectedFileNames = [
        '.htaccess',
        'index.php',
        'config.yml',
        'config.yaml',
        '.env',
        '.env.local',
        '.env.production',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'yarn.lock',
        'boot.php',
        'install.php',
    ];

    /**
     * @return array{success: bool, data?: array<int, array<string, mixed>>, error?: string}
     */
    public function listDirectory(string $path): array
    {
        $fullPath = $this->resolveAllowedDirectory($path);

        if (null === $fullPath || !is_dir($fullPath)) {
            return ['success' => false, 'error' => 'Directory not found or not allowed'];
        }

        $items = [];
        $files = scandir($fullPath);

        if (false === $files) {
            return ['success' => false, 'error' => 'Could not read directory'];
        }

        foreach ($files as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $filePath = $fullPath . '/' . $file;
            $relativePath = trim($path . '/' . $file, '/');

            if (is_dir($filePath)) {
                if (!$this->isExcludedDirectory($file)) {
                    $items[] = [
                        'name' => $file,
                        'path' => $relativePath,
                        'type' => 'folder',
                        'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                    ];
                }

                continue;
            }

            $extension = strtolower((string) rex_file::extension($file));
            if ($this->isAllowedExtension($extension)) {
                $items[] = [
                    'name' => $file,
                    'path' => $relativePath,
                    'type' => 'file',
                    'extension' => $extension,
                    'size_bytes' => filesize($filePath),
                    'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'writable' => is_writable($filePath),
                ];
            }
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return 'folder' === $a['type'] ? -1 : 1;
            }

            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return ['success' => true, 'data' => $items];
    }

    /**
     * @return array{success: bool, data?: array<string, string>, error?: string}
     */
    public function createEntry(string $path, string $name, string $type = 'file'): array
    {
        $trimmedName = trim($name);
        if ('' === $trimmedName) {
            return ['success' => false, 'error' => 'Name is required'];
        }

        if (1 === preg_match('/[\/\\\\:\*\?"<>\|]/', $trimmedName)) {
            return ['success' => false, 'error' => 'Name contains invalid characters'];
        }

        $parentPath = $this->resolveAllowedDirectory($path);
        if (null === $parentPath || !is_dir($parentPath)) {
            return ['success' => false, 'error' => 'Directory not found or not allowed'];
        }

        $newPath = $parentPath . '/' . $trimmedName;
        if (file_exists($newPath)) {
            return ['success' => false, 'error' => 'File or folder already exists'];
        }

        if ('folder' === $type) {
            if (!rex_dir::create($newPath)) {
                return ['success' => false, 'error' => 'Failed to create folder'];
            }

            return [
                'success' => true,
                'data' => [
                    'path' => trim($path . '/' . $trimmedName, '/'),
                    'type' => 'folder',
                ],
            ];
        }

        $extension = strtolower((string) rex_file::extension($trimmedName));
        if (!$this->isAllowedExtension($extension)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        $content = $this->getDefaultFileContent($extension);
        if (false === rex_file::put($newPath, $content)) {
            return ['success' => false, 'error' => 'Failed to create file'];
        }

        return [
            'success' => true,
            'data' => [
                'path' => trim($path . '/' . $trimmedName, '/'),
                'type' => 'file',
            ],
        ];
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function readFile(string $path): array
    {
        $fullPath = $this->resolveAllowedFile($path);
        if (null === $fullPath || !is_file($fullPath)) {
            return ['success' => false, 'error' => 'File not found or not allowed'];
        }

        $extension = strtolower((string) rex_file::extension($fullPath));
        if (!$this->isAllowedExtension($extension)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        $content = rex_file::get($fullPath);
        if (false === $content) {
            return ['success' => false, 'error' => 'Failed to read file'];
        }

        return [
            'success' => true,
            'data' => [
                'path' => trim($path, '/'),
                'name' => basename($fullPath),
                'extension' => $extension,
                'content' => $content,
                'size_bytes' => filesize($fullPath),
                'writable' => is_writable($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
            ],
        ];
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function saveFile(string $path, string $content): array
    {
        $fullPath = $this->resolveAllowedFile($path);
        if (null === $fullPath || !is_file($fullPath)) {
            return ['success' => false, 'error' => 'File not found or not allowed'];
        }

        $extension = strtolower((string) rex_file::extension($fullPath));
        if (!$this->isAllowedExtension($extension)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        if (!is_writable($fullPath)) {
            return ['success' => false, 'error' => 'File is not writable'];
        }

        if (false === rex_file::put($fullPath, $content)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }

        return [
            'success' => true,
            'data' => [
                'path' => trim($path, '/'),
                'size_bytes' => filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
            ],
        ];
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function deleteFile(string $path): array
    {
        $fullPath = $this->resolveAllowedFile($path);
        if (null === $fullPath || !is_file($fullPath)) {
            return ['success' => false, 'error' => 'File not found or not allowed'];
        }

        $extension = strtolower((string) rex_file::extension($fullPath));
        if (!$this->isAllowedExtension($extension)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        if ($this->isProtectedFile($fullPath)) {
            return ['success' => false, 'error' => 'Protected file cannot be deleted'];
        }

        if (!is_writable($fullPath)) {
            return ['success' => false, 'error' => 'File is not writable'];
        }

        if (!rex_file::delete($fullPath)) {
            return ['success' => false, 'error' => 'Failed to delete file'];
        }

        return [
            'success' => true,
            'data' => [
                'path' => trim($path, '/'),
                'deleted' => true,
            ],
        ];
    }

    private function resolveAllowedDirectory(string $path): ?string
    {
        $basePath = (string) realpath(rex_path::base());
        if ('' === $basePath) {
            return null;
        }

        $fullPath = $basePath . '/' . ltrim($path, '/');
        $realPath = realpath($fullPath);

        if (false === $realPath) {
            return null;
        }

        if (strpos($realPath, $basePath) !== 0) {
            return null;
        }

        return $realPath;
    }

    private function resolveAllowedFile(string $path): ?string
    {
        $basePath = (string) realpath(rex_path::base());
        if ('' === $basePath) {
            return null;
        }

        $fullPath = $basePath . '/' . ltrim($path, '/');
        $realPath = realpath($fullPath);

        if (false === $realPath) {
            return null;
        }

        if (strpos($realPath, $basePath) !== 0) {
            return null;
        }

        return $realPath;
    }

    private function isAllowedExtension(string $extension): bool
    {
        return in_array($extension, EditorConfig::getAllowedExtensions(), true);
    }

    private function isExcludedDirectory(string $directoryName): bool
    {
        return in_array($directoryName, EditorConfig::getExcludedDirectories(), true);
    }

    private function getDefaultFileContent(string $extension): string
    {
        return match ($extension) {
            'php' => "<?php\n\n",
            'html', 'htm' => "<!DOCTYPE html>\n<html lang=\"de\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>New Document</title>\n</head>\n<body>\n\n</body>\n</html>\n",
            'css' => "/* New stylesheet */\n",
            'scss', 'less' => "// New stylesheet\n",
            'js', 'ts', 'jsx', 'tsx' => "// New script file\n",
            'json' => "{\n}\n",
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root>\n</root>\n",
            'md', 'rst' => "# New document\n",
            'sql' => "-- New SQL file\n",
            'yml', 'yaml' => "# New YAML file\n",
            default => '',
        };
    }

    private function isProtectedFile(string $filePath): bool
    {
        $fileName = basename($filePath);
        return in_array($fileName, $this->protectedFileNames, true);
    }
}
