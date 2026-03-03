<?php

namespace FriendsOfRedaxo\Code\Api\RoutePackage;

use Exception;
use FriendsOfRedaxo\Api\Auth\BearerAuth;
use FriendsOfRedaxo\Api\RouteCollection;
use FriendsOfRedaxo\Api\RoutePackage;
use FriendsOfRedaxo\Code\Api\CodeFileService;
use FriendsOfRedaxo\Code\EditorConfig;
use rex_addon;
use rex;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

use const JSON_PRETTY_PRINT;

class Code extends RoutePackage
{
    public function loadRoutes(): void
    {
        RouteCollection::registerRoute(
            'code/capabilities',
            new Route(
                'code/capabilities',
                [
                    '_controller' => 'FriendsOfRedaxo\Code\Api\RoutePackage\Code::handleCapabilities',
                ],
                [],
                [],
                '',
                [],
                ['GET'],
            ),
            'Get code addon editor capabilities (allowed file formats and excluded directories)',
            null,
            new BearerAuth(),
            ['code'],
        );

        RouteCollection::registerRoute(
            'code/files/list',
            new Route(
                'code/files',
                [
                    '_controller' => 'FriendsOfRedaxo\Code\Api\RoutePackage\Code::handleFilesList',
                    'query' => [
                        'path' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => '',
                        ],
                    ],
                ],
                [],
                [],
                '',
                [],
                ['GET'],
            ),
            'Browse files and folders in the REDAXO base path',
            null,
            new BearerAuth(),
            ['code'],
        );

        RouteCollection::registerRoute(
            'code/files/create',
            new Route(
                'code/files',
                [
                    '_controller' => 'FriendsOfRedaxo\Code\Api\RoutePackage\Code::handleCreateFile',
                    'Body' => [
                        'path' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => '',
                        ],
                        'name' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'type' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => 'file',
                        ],
                    ],
                ],
                [],
                [],
                '',
                [],
                ['POST'],
            ),
            'Create a file or folder in the REDAXO base path',
            null,
            new BearerAuth(),
            ['code'],
        );

        RouteCollection::registerRoute(
            'code/file/read',
            new Route(
                'code/file',
                [
                    '_controller' => 'FriendsOfRedaxo\Code\Api\RoutePackage\Code::handleReadFile',
                    'query' => [
                        'path' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                ],
                [],
                [],
                '',
                [],
                ['GET'],
            ),
            'Read file content for allowed file types',
            null,
            new BearerAuth(),
            ['code'],
        );

        RouteCollection::registerRoute(
            'code/file/update',
            new Route(
                'code/file',
                [
                    '_controller' => 'FriendsOfRedaxo\Code\Api\RoutePackage\Code::handleSaveFile',
                    'Body' => [
                        'path' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'content' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                ],
                [],
                [],
                '',
                [],
                ['PUT', 'PATCH'],
            ),
            'Save file content for allowed file types',
            null,
            new BearerAuth(),
            ['code'],
        );

        RouteCollection::registerRoute(
            'code/file/delete',
            new Route(
                'code/file',
                [
                    '_controller' => 'FriendsOfRedaxo\Code\Api\RoutePackage\Code::handleDeleteFile',
                    'query' => [
                        'path' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                ],
                [],
                [],
                '',
                [],
                ['DELETE'],
            ),
            'Delete allowed files (protected files are blocked)',
            null,
            new BearerAuth(),
            ['code'],
        );
    }

    /** @api */
    public static function handleCapabilities($parameter, array $route = []): Response
    {
        $addon = rex_addon::get('code');

        $payload = [
            'addon' => 'code',
            'version' => $addon->getVersion(),
            'file_browser_enabled' => (bool) $addon->getConfig('enable_file_browser', true),
            'allowed_extensions' => EditorConfig::getAllowedExtensions(),
            'excluded_directories' => EditorConfig::getExcludedDirectories(),
        ];

        return new Response(json_encode($payload, JSON_PRETTY_PRINT));
    }

    /** @api */
    public static function handleFilesList($parameter, array $route = []): Response
    {
        $addon = rex_addon::get('code');
        if (!(bool) $addon->getConfig('enable_file_browser', true)) {
            return new Response(json_encode(['error' => 'File browser is disabled']), 403);
        }

        try {
            $query = RouteCollection::getQuerySet($_REQUEST, $parameter['query']);
        } catch (Exception $e) {
            return new Response(json_encode(['error' => 'query field: ' . $e->getMessage() . ' is required']), 400);
        }

        $service = new CodeFileService();
        $result = $service->listDirectory((string) ($query['path'] ?? ''));

        if (!(bool) ($result['success'] ?? false)) {
            return new Response(json_encode(['error' => $result['error'] ?? 'Unknown error']), 400);
        }

        return new Response(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT));
    }

    /** @api */
    public static function handleCreateFile($parameter, array $route = []): Response
    {
        $addon = rex_addon::get('code');
        if (!(bool) $addon->getConfig('enable_file_browser', true)) {
            return new Response(json_encode(['error' => 'File browser is disabled']), 403);
        }

        $data = json_decode(rex::getRequest()->getContent(), true);
        if (!is_array($data)) {
            return new Response(json_encode(['error' => 'Invalid input']), 400);
        }

        try {
            $body = RouteCollection::getQuerySet($data, $parameter['Body']);
        } catch (Exception $e) {
            return new Response(json_encode(['error' => 'Body field: `' . $e->getMessage() . '` is required']), 400);
        }

        $type = (string) ($body['type'] ?? 'file');
        if ('file' !== $type && 'folder' !== $type) {
            return new Response(json_encode(['error' => 'Invalid type. Use file or folder']), 400);
        }

        $service = new CodeFileService();
        $result = $service->createEntry(
            (string) ($body['path'] ?? ''),
            (string) ($body['name'] ?? ''),
            $type,
        );

        if (!(bool) ($result['success'] ?? false)) {
            return new Response(json_encode(['error' => $result['error'] ?? 'Unknown error']), 400);
        }

        return new Response(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT), 201);
    }

    /** @api */
    public static function handleReadFile($parameter, array $route = []): Response
    {
        $addon = rex_addon::get('code');
        if (!(bool) $addon->getConfig('enable_file_browser', true)) {
            return new Response(json_encode(['error' => 'File browser is disabled']), 403);
        }

        try {
            $query = RouteCollection::getQuerySet($_REQUEST, $parameter['query']);
        } catch (Exception $e) {
            return new Response(json_encode(['error' => 'query field: ' . $e->getMessage() . ' is required']), 400);
        }

        $service = new CodeFileService();
        $result = $service->readFile((string) ($query['path'] ?? ''));

        if (!(bool) ($result['success'] ?? false)) {
            return new Response(json_encode(['error' => $result['error'] ?? 'Unknown error']), 400);
        }

        return new Response(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT));
    }

    /** @api */
    public static function handleSaveFile($parameter, array $route = []): Response
    {
        $addon = rex_addon::get('code');
        if (!(bool) $addon->getConfig('enable_file_browser', true)) {
            return new Response(json_encode(['error' => 'File browser is disabled']), 403);
        }

        $data = json_decode(rex::getRequest()->getContent(), true);
        if (!is_array($data)) {
            return new Response(json_encode(['error' => 'Invalid input']), 400);
        }

        try {
            $body = RouteCollection::getQuerySet($data, $parameter['Body']);
        } catch (Exception $e) {
            return new Response(json_encode(['error' => 'Body field: `' . $e->getMessage() . '` is required']), 400);
        }

        $service = new CodeFileService();
        $result = $service->saveFile(
            (string) ($body['path'] ?? ''),
            (string) ($body['content'] ?? ''),
        );

        if (!(bool) ($result['success'] ?? false)) {
            return new Response(json_encode(['error' => $result['error'] ?? 'Unknown error']), 400);
        }

        return new Response(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT), 200);
    }

    /** @api */
    public static function handleDeleteFile($parameter, array $route = []): Response
    {
        $addon = rex_addon::get('code');
        if (!(bool) $addon->getConfig('enable_file_browser', true)) {
            return new Response(json_encode(['error' => 'File browser is disabled']), 403);
        }

        try {
            $query = RouteCollection::getQuerySet($_REQUEST, $parameter['query']);
        } catch (Exception $e) {
            return new Response(json_encode(['error' => 'query field: ' . $e->getMessage() . ' is required']), 400);
        }

        $service = new CodeFileService();
        $result = $service->deleteFile((string) ($query['path'] ?? ''));

        if (!(bool) ($result['success'] ?? false)) {
            return new Response(json_encode(['error' => $result['error'] ?? 'Unknown error']), 400);
        }

        return new Response(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT), 200);
    }
}
