<?php

namespace FriendsOfRedaxo\Code\Api\RoutePackage\Backend;

use FriendsOfRedaxo\Api\Auth\BackendUser;
use FriendsOfRedaxo\Api\RouteCollection;
use FriendsOfRedaxo\Code\Api\RoutePackage\Code as TokenCode;

use function strlen;

class Code extends TokenCode
{
    public function loadRoutes(): void
    {
        $routes = RouteCollection::getRoutes();

        foreach ($routes as $route) {
            if ('code/' === substr($route['scope'], 0, strlen('code/'))) {
                $scope = 'backend/' . $route['scope'];
                $newRoute = clone $route['route'];
                $newRoute->setPath('backend' . $newRoute->getPath());

                RouteCollection::registerRoute(
                    $scope,
                    $newRoute,
                    $route['description'],
                    $route['responses'],
                    new BackendUser(),
                    ['backend'],
                );
            }
        }
    }
}
