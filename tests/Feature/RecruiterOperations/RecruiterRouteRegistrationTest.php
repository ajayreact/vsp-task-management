<?php

use App\Http\Controllers\RecruiterOperations\RecruiterDashboardController;
use App\Modules\RecruiterOperations\Providers\RecruiterOperationsServiceProvider;
use App\Modules\TaskManagement\Providers\TaskManagementServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;

/*
|--------------------------------------------------------------------------
| Route registration and the public share catch-all
|--------------------------------------------------------------------------
|
| routes/share.php ends with `{companySlug}/{shortCode}`, where the short code
| is 8-10 alphanumerics. Laravel matches routes in registration order, so a
| two-segment recruiter URL such as /recruiter/training is only safe when the
| Recruiter Operations routes are registered before that catch-all.
|
*/

const RECRUITER_FUTURE_PATHS = ['/recruiter/training', '/recruiter/questions', '/recruiter/activities'];

function recruiterStandInRoute(string $path): Route
{
    return new Route(['GET', 'HEAD'], ltrim($path, '/'), ['as' => 'recruiter.stand-in', 'uses' => fn () => null]);
}

function publicShareCatchAll(): Route
{
    $route = app('router')->getRoutes()->getByName('share.client.show');

    expect($route)->not->toBeNull();

    return $route;
}

test('the recruiter dashboard is mounted at /recruiter', function () {
    expect(route('recruiter.dashboard', absolute: false))->toBe('/recruiter');

    $route = app('router')->getRoutes()->match(Request::create('/recruiter', 'GET'));

    expect($route->getName())->toBe('recruiter.dashboard')
        ->and($route->getControllerClass())->toBe(RecruiterDashboardController::class)
        ->and($route->gatherMiddleware())->toContain('web', 'auth', 'internal', 'permission:recruiter.access');
});

test('the recruiter provider is registered before task management', function () {
    $providers = require base_path('bootstrap/providers.php');

    $recruiter = array_search(RecruiterOperationsServiceProvider::class, $providers, true);
    $taskManagement = array_search(TaskManagementServiceProvider::class, $providers, true);

    expect($recruiter)->toBeInt()
        ->and($taskManagement)->toBeInt()
        ->and($recruiter)->toBeLessThan($taskManagement);
});

test('every recruiter route is registered ahead of the public share catch-all', function () {
    $routes = app('router')->getRoutes()->getRoutes();
    $names = array_map(fn (Route $route) => $route->getName(), $routes);

    $sharePosition = array_search('share.client.show', $names, true);
    $recruiterPositions = array_keys(array_filter(
        $names,
        fn (?string $name) => $name !== null && str_starts_with($name, 'recruiter.'),
    ));

    expect($sharePosition)->toBeInt()
        ->and($recruiterPositions)->not->toBeEmpty();

    foreach ($recruiterPositions as $position) {
        expect($position)->toBeLessThan($sharePosition);
    }
});

test('the share catch-all would capture two-segment recruiter paths on its own', function (string $path) {
    expect(publicShareCatchAll()->matches(Request::create($path, 'GET')))->toBeTrue();
})->with(RECRUITER_FUTURE_PATHS);

test('the share catch-all never captures the recruiter root', function () {
    expect(publicShareCatchAll()->matches(Request::create('/recruiter', 'GET')))->toBeFalse();
});

test('recruiter paths registered ahead of the catch-all are not captured by it', function (string $path) {
    $routes = new RouteCollection;
    $routes->add(recruiterStandInRoute($path));
    $routes->add(publicShareCatchAll());

    expect($routes->match(Request::create($path, 'GET'))->getName())->toBe('recruiter.stand-in');
})->with([...RECRUITER_FUTURE_PATHS, '/recruiter']);

test('registering recruiter paths after the catch-all would let it capture them', function (string $path) {
    $routes = new RouteCollection;
    $routes->add(publicShareCatchAll());
    $routes->add(recruiterStandInRoute($path));

    expect($routes->match(Request::create($path, 'GET'))->getName())->toBe('share.client.show');
})->with(RECRUITER_FUTURE_PATHS);

test('the live recruiter root is served by recruiter operations, not the share controller', function () {
    $this->actingAs(superAdmin())
        ->get('/recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/dashboard'));
});
