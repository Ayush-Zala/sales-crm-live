<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/lead?columnFilters=%255B%257B%2522id%2522%253A%2522name%2522%252C%2522value%2522%253A%2522smoke%2522%257D%255D', 'GET');
$controller = app()->make(\App\Http\Controllers\LeadController::class);

$response = $controller->index($request);

// Inertia response data is available in the view data or via the Response object
$props = $response->toResponse($request)->getOriginalContent()->getData();
$leads = $props['page']['props']['leadsData']['data'] ?? [];

echo "Found " . count($leads) . " leads.\n";
foreach ($leads as $lead) {
    echo "- " . $lead['name'] . "\n";
}
