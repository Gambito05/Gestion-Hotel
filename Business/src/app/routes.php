<?php

namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use App\controllers\Paciente;


$app->group('/api', function (RouteCollectorProxy $api) {
    $api->group('/pacientes', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('/filter/{offset}/{limit}', Paciente::class . ':filter');
        $endpoint->get('[/{id}]', Paciente::class . ':read');
        $endpoint->post('', Paciente::class . ':create');
        $endpoint->put('/{id}', Paciente::class . ':update');
        $endpoint->delete('/{id}', Paciente::class . ':delete');
    });
});
