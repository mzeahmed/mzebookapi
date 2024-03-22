<?php

declare(strict_types=1);

namespace App\Core\Http\Router;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

class Route
{

    /**
     * @var string $path The path to match against the URL.
     */
    private string $path;

    /**
     * @var callable $callback The callback to execute if the route matches.
     */
    private $callback;

    /**
     * @var array $matches The parameters to match against the URL.
     */
    private array $matches = [];

    /**
     * @var array $params The parameters to add to the route.
     */
    private array $params = [];

    /**
     * @var Container $container The DI container instance.
     */
    private Container $container;

    public function __construct(string $path, mixed $callback, Container $container)
    {
        $this->path = trim($path, '/');
        $this->callback = $callback;
        $this->container = $container;
    }

    /**
     * Adds a parameter to the route.
     *
     * @param string $param The parameter to add.
     * @param string $regex The regular expression to match against the parameter.
     *
     * @return $this
     */
    public function with(string $param, string $regex): self
    {
        $this->params[$param] = str_replace('(', '(?:', $regex);

        return $this;
    }

    /**
     * Checks if the route matches the given URL.
     *
     * @param string $url The URL to match against.
     *
     * @return bool True if the route matches the URL, false otherwise.
     */
    public function match(string $url): bool
    {
        $url = trim($url, '/');
        $path = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], $this->path);
        $regex = "#^$path$#i";

        if (!preg_match($regex, $url, $matches)) {
            return false;
        }

        array_shift($matches);

        $this->matches = $matches;

        return true;
    }

    /**
     * Calls the route's callback.
     *
     * @return mixed
     */
    public function call(): mixed
    {
        if (is_string($this->callback)) {
            $controllerAction = explode('@', $this->callback);
            [$controllerName, $action] = $controllerAction;
            try {
                $controllerInstance = $this->container->get($controllerName);
            } catch (DependencyException|NotFoundException $e) {
                return $e->getMessage();
            }

            return call_user_func_array([$controllerInstance, $action], $this->matches);
        }

        return call_user_func_array($this->callback, $this->matches);
    }

    /**
     * Replaces the parameter with the regular expression.
     *
     * @param array $match The match to replace.
     *
     * @return string The regular expression.
     */
    private function paramMatch(array $match): string
    {
        if (isset($this->params[$match[1]])) {
            return '(' . $this->params[$match[1]] . ')';
        }

        return '([^/]+)';
    }

    /**
     * Gets the URL for the route.
     *
     * @param array $params The parameters to add to the URL.
     *
     * @return string The URL for the route.
     */
    public function getUrl(array $params): string
    {
        $path = $this->path;

        foreach ($params as $k => $v) {
            $path = str_replace(":$k", $v, $path);
        }

        return $path;
    }
}
