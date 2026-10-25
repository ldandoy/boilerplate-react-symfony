<?php

namespace App\Tests\Architecture;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Vérifie sur le routeur réel que /api n'est servi que par API Platform (ou les bundles d'auth)
 * et que les seuls contrôleurs applicatifs sont ceux d'EasyAdmin, de « / » et de /login.
 */
class RoutingArchitectureTest extends KernelTestCase
{
    private const ALLOWED_APP_CONTROLLERS = [
        'App\\Controller\\HomeController',
        'App\\Controller\\SecurityController',
    ];

    public function testApiRoutesAreNotServedByAppControllers(): void
    {
        foreach ($this->routes() as $name => [$path, $controller]) {
            if (!str_starts_with($path, '/api')) {
                continue;
            }
            $this->assertStringStartsNotWith(
                'App\\',
                $controller,
                sprintf('Route « %s » (%s) : une route /api passe par API Platform, pas par un contrôleur (règle d\'or 1).', $name, $path),
            );
        }
    }

    public function testAppControllersAreBackOfficeOnly(): void
    {
        foreach ($this->routes() as $name => [$path, $controller]) {
            if (!str_starts_with($controller, 'App\\')) {
                continue;
            }
            $class = explode('::', $controller)[0];
            $this->assertTrue(
                str_starts_with($class, 'App\\Controller\\Admin\\') || \in_array($class, self::ALLOWED_APP_CONTROLLERS, true),
                sprintf('Route « %s » (%s) servie par %s : seul le back-office EasyAdmin a des contrôleurs (règle d\'or 2).', $name, $path, $class),
            );
        }
    }

    /** @return array<string, array{string, string}> */
    private function routes(): array
    {
        self::bootKernel();
        $routes = [];
        foreach (static::getContainer()->get(RouterInterface::class)->getRouteCollection() as $name => $route) {
            $controller = $route->getDefault('_controller');
            $routes[$name] = [$route->getPath(), \is_string($controller) ? $controller : ''];
        }

        return $routes;
    }
}
