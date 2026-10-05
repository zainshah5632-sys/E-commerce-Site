<?php
declare(strict_types=1);

namespace App\Core;

use App\Repositories\ProfileRepository;
use RuntimeException;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $pattern = $this->compilePattern($path);
        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $path,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $matchedRoute = null;
        $matchedParams = [];
        $pathMatchedOtherMethod = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                if ($route['method'] === $method) {
                    $matchedRoute = $route;
                    $matchedParams = array_filter(
                        $matches,
                        static fn($k) => !is_int($k),
                        ARRAY_FILTER_USE_KEY
                    );
                    break;
                }
                $pathMatchedOtherMethod = true;
            }
        }

        if ($matchedRoute === null) {
            if ($pathMatchedOtherMethod) {
                return Response::html(View::render('pages/404', ['title' => '405 Method Not Allowed']), 405);
            }
            return Response::html(View::render('pages/404', ['title' => 'Page Not Found']), 404);
        }

        $request->setRouteParams($matchedParams);

        // Run middleware pipeline
        $middlewareResult = $this->runMiddleware($matchedRoute['middleware'], $request);
        if ($middlewareResult instanceof Response) {
            return $middlewareResult;
        }

        // Execute controller handler
        $handler = $matchedRoute['handler'];
        if (is_array($handler)) {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException("Controller class {$class} not found.");
            }
            $controller = new $class();
            if (!method_exists($controller, $action)) {
                throw new RuntimeException("Method {$action} not found on controller {$class}.");
            }
            $response = $controller->$action($request);
        } else {
            $response = $handler($request);
        }

        if ($response instanceof Response) {
            return $response;
        }

        return Response::html((string)$response);
    }

    private function runMiddleware(array $middlewares, Request $request): ?Response
    {
        $session = Session::instance();

        foreach ($middlewares as $mw) {
            switch ($mw) {
                case 'csrf':
                    if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                        $token = (string)($request->input('_csrf') ?? $request->header('X-CSRF-Token', ''));
                        if (!$session->validateCsrf($token)) {
                            return Response::html(View::render('pages/419', ['title' => 'Page Expired']), 419);
                        }
                    }
                    break;

                case 'auth':
                    if (!$session->isAuthenticated()) {
                        $next = $request->path();
                        return Response::redirect('/login?next=' . urlencode($next));
                    }
                    break;

                case 'guest':
                    if ($session->isAuthenticated()) {
                        return Response::redirect('/account');
                    }
                    break;

                case 'admin':
                    if (!$session->isAuthenticated()) {
                        return Response::redirect('/login?next=' . urlencode($request->path()));
                    }
                    // Re-check role from DB per AR §10
                    $uid = $session->userId();
                    $isAdmin = false;
                    if ($uid !== null) {
                        $profile = ProfileRepository::find($uid);
                        if ($profile && ($profile['role'] ?? '') === 'admin') {
                            $isAdmin = true;
                        }
                    }

                    if (!$isAdmin) {
                        return Response::html(View::render('pages/403', ['title' => 'Access Denied']), 403);
                    }
                    break;
            }
        }

        return null;
    }

    private function compilePattern(string $path): string
    {
        // Replace {slug}, {id}, {number} with regexes
        $pattern = preg_replace('/\{slug\}/', '(?P<slug>[a-z0-9-]+)', $path);
        $pattern = preg_replace('/\{id\}/', '(?P<id>[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})', (string)$pattern);
        $pattern = preg_replace('/\{number\}/', '(?P<number>ORD-\d{6}-[A-Z0-9]{6})', (string)$pattern);

        return '#^' . $pattern . '$#u';
    }
}
