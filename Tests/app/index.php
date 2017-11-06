<?php
namespace Sorien\DataGridBundle\Tests\app;

use Symfony\Component\HttpFoundation\Request;

include __DIR__ . '/../../vendor/autoload.php';

$request = Request::createFromGlobals();
$kernel = new AppKernel('dev', true);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
