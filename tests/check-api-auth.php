<?php

/**
 * Asserts that every worklog API route requires authentication (ticket #2627).
 *
 * The package has no test suite or testbench yet, so this script registers
 * routes/api.php on a bare Illuminate router (no database, no host boot) and
 * checks each route's middleware:
 *   - every route carries 'auth:api' and does not exclude it, except the
 *     routes listed in PUBLIC_ROUTES;
 *   - no debug 'test-*' route is registered;
 *   - at least one route is registered and every route in REQUIRED_ROUTES is
 *     present, so an empty or wrongly loaded routes file cannot pass.
 *
 * It needs an autoloader that provides illuminate/routing, normally the host
 * application's vendor/autoload.php. Usage:
 *   php tests/check-api-auth.php [routes-file] [--autoload=/path/to/vendor/autoload.php]
 * Without --autoload it tries this package's vendor, then the host's vendor
 * when the package sits at <host>/packages/dooz/worklog.
 * Exit code 0 = pass, 1 = violations, 2 = setup error.
 */

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;

const REQUIRED_MIDDLEWARE = 'auth:api';
// Deliberately public: a constant liveness payload that reads and changes nothing.
const PUBLIC_ROUTES = ['GET api/worklog/health'];
// Known routes that must be registered: one public, one authenticated.
const REQUIRED_ROUTES = ['GET api/worklog/health', 'POST api/worklog/tasks/from-quicky'];

$routesFile = __DIR__ . '/../routes/api.php';
$autoload = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--autoload=')) {
        $autoload = substr($arg, strlen('--autoload='));
    } else {
        $routesFile = $arg;
    }
}
$candidates = $autoload !== null ? [$autoload] : [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];
$found = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $found = $candidate;
        break;
    }
}
if ($found === null || ! is_file($routesFile)) {
    fwrite(STDERR, "Setup error: need illuminate/routing autoload (tried: " . implode(', ', $candidates) . ") and routes file ({$routesFile}).\n");
    exit(2);
}
require $found;

$app = new Container();
Container::setInstance($app);
$router = new Router(new Dispatcher($app), $app);
$app->instance('router', $router);
Facade::setFacadeApplication($app);

require $routesFile;

$violations = [];
$rows = [];
$keys = [];
foreach ($router->getRoutes()->getRoutes() as $route) {
    $methods = array_values(array_diff($route->methods(), ['HEAD']));
    $key = implode('|', $methods) . ' ' . $route->uri();
    $middleware = $route->middleware();
    $excluded = $route->excludedMiddleware();
    $authenticated = in_array(REQUIRED_MIDDLEWARE, $middleware, true)
        && ! in_array(REQUIRED_MIDDLEWARE, $excluded, true);
    $public = in_array($key, PUBLIC_ROUTES, true);
    $keys[] = $key;

    $rows[] = sprintf('%-7s %-48s %s%s', implode('|', $methods), $route->uri(), implode(',', $middleware),
        $excluded ? '  without:' . implode(',', $excluded) : '');

    if (str_contains($route->uri(), 'test-')) {
        $violations[] = "debug route still registered: {$key}";
    } elseif (! $authenticated && ! $public) {
        $violations[] = "no " . REQUIRED_MIDDLEWARE . ": {$key}";
    }
}

if ($rows === []) {
    $violations[] = "no routes registered from {$routesFile}";
}
foreach (array_diff(REQUIRED_ROUTES, $keys) as $missing) {
    $violations[] = "expected route missing: {$missing}";
}

echo implode("\n", $rows) . "\n\n";
echo count($rows) . " routes, " . count($violations) . " violations\n";
foreach ($violations as $violation) {
    echo "FAIL {$violation}\n";
}
exit($violations ? 1 : 0);
