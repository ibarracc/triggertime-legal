<?php

/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    // Register our authentication middlewares
    $routes->registerMiddleware('jwt', new \App\Middleware\JwtMiddleware());
    $routes->registerMiddleware('adminRole', new \App\Middleware\AdminRoleMiddleware());
    $routes->registerMiddleware('apiKey', new \App\Middleware\ApiKeyMiddleware());

    // Provide the JSON extension for all API routes
    $routes->scope('/api', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json']);

        // Swagger UI redirect
        $builder->redirect('/docs', '/api-docs/index.html', ['status' => 301]);


        // Version 1 API - Mapping to App\Controller\Api\V1
        $builder->prefix('Api/V1', ['path' => '/v1'], function (RouteBuilder $v1): void {
            // Stripe webhook (signature verification only, no JWT)
            $v1->post('/webhooks/stripe', ['controller' => 'Webhooks', 'action' => 'stripe']);

            // ── A. Mobile App Endpoints (API Key auth) ──
            $v1->scope('/', function (RouteBuilder $mobile): void {
                $mobile->applyMiddleware('apiKey');
                $mobile->get('/app/config', ['controller' => 'AppConfig', 'action' => 'index']);
                $mobile->post('/devices/register', ['controller' => 'Devices', 'action' => 'register']);
                $mobile->post('/devices/activate', ['controller' => 'Devices', 'action' => 'activate']);
                $mobile->get('/devices/{device_uuid}/status', ['controller' => 'Devices', 'action' => 'status'])->setPass(['device_uuid']);
                $mobile->post('/devices/upgrade-token', ['controller' => 'UpgradeTokens', 'action' => 'generate']);
                $mobile->post('/devices/link-code', ['controller' => 'UpgradeTokens', 'action' => 'generateLinkCode']);
                $mobile->get('/devices/{device_uuid}/license-status', ['controller' => 'Devices', 'action' => 'licenseStatus'])->setPass(['device_uuid']);
                $mobile->post('/devices/{device_uuid}/transfer-license', ['controller' => 'Devices', 'action' => 'transferLicense'])->setPass(['device_uuid']);
            });

            // ── B/C. Web Endpoints ──
            $v1->prefix('Web', function (RouteBuilder $web): void {
                // Public endpoints (no auth)
                $web->get('/tokens/{token}/verify', ['controller' => 'Tokens', 'action' => 'verify'])->setPass(['token']);
                $web->post('/auth/login', ['controller' => 'Auth', 'action' => 'login']);
                $web->post('/auth/register', ['controller' => 'Auth', 'action' => 'register']);
                $web->post('/auth/forgot-password', ['controller' => 'Auth', 'action' => 'forgotPassword']);
                $web->post('/auth/reset-password', ['controller' => 'Auth', 'action' => 'resetPassword']);

                // Authenticated Endpoints (JWT)
                $web->scope('/', function (RouteBuilder $webAuth): void {
                    $webAuth->applyMiddleware('jwt');
                    $webAuth->post('/devices/link', ['controller' => 'Devices', 'action' => 'link']);
                    $webAuth->post('/devices/link-upgrade-token', ['controller' => 'Devices', 'action' => 'linkUpgradeToken']);
                    $webAuth->post('/devices/{device_uuid}/unlink', ['controller' => 'Devices', 'action' => 'unlink'])->setPass(['device_uuid']);
                    $webAuth->put('/devices/{device_uuid}', ['controller' => 'Devices', 'action' => 'update'])->setPass(['device_uuid']);
                    $webAuth->get('/me', ['controller' => 'Auth', 'action' => 'me']);
                    $webAuth->post('/me/profile', ['controller' => 'Auth', 'action' => 'updateProfile']);
                    $webAuth->post('/me/password', ['controller' => 'Auth', 'action' => 'updatePassword']);
                    $webAuth->get('/devices', ['controller' => 'Devices', 'action' => 'index']);
                    $webAuth->post('/subscriptions/checkout', ['controller' => 'Subscriptions', 'action' => 'createCheckout']);
                    $webAuth->post('/subscriptions/portal', ['controller' => 'Subscriptions', 'action' => 'portal']);
                });
            });

            // ── D. Admin Endpoints (JWT + admin role) ──
            $v1->prefix('Admin', function (RouteBuilder $admin): void {
                $admin->applyMiddleware('jwt', 'adminRole');
                $admin->resources('Users');
                $admin->post('/users/{id}/impersonate', ['controller' => 'Users', 'action' => 'impersonate'])->setPass(['id']);
                $admin->resources('Licenses');
                $admin->post('/licenses/import', ['controller' => 'Licenses', 'action' => 'importCsv']);
                $admin->post('/licenses/{id}/toggle-active', ['controller' => 'Licenses', 'action' => 'toggleActive'])->setPass(['id']);
                $admin->resources('Versions');
                $admin->resources('RemoteConfig');
                $admin->resources('Instances');
                $admin->resources('Subscriptions');
                $admin->resources('Devices'); // Added missing Devices resource routing
            });
        });
    });

    // SPA Catch-All
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'spa']);
        $builder->connect('/*', ['controller' => 'Pages', 'action' => 'spa']);
    });
};
