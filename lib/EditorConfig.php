<?php

namespace FriendsOfRedaxo\Code;

final class EditorConfig
{
    public static function getAllowedExtensions(): array
    {
        return [
            'php', 'html', 'htm', 'css', 'scss', 'less', 'js', 'json', 'xml', 'sql',
            'md', 'txt', 'csv', 'tsv', 'log', 'rst', 'toml', 'cfg', 'properties',
            'yml', 'yaml', 'ini', 'conf', 'htaccess', 'gitignore', 'env',
            'twig', 'vue', 'ts', 'jsx', 'tsx', 'py', 'rb', 'go', 'java', 'c', 'cpp',
        ];
    }

    public static function getExcludedDirectories(): array
    {
        return [
            'node_modules', '.git', '.svn', 'vendor', 'cache', 'log', 'tmp', 'temp',
        ];
    }
}
