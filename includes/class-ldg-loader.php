<?php
/**
 * Plugin Loader Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all actions and filters for the plugin
 */
class LdgLoader
{
    /**
     * Array of actions registered with WordPress
     *
     * @var array
     */
    protected array $actions = [];

    /**
     * Array of filters registered with WordPress
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * Add a new action to the collection
     *
     * @param string $hook The name of the WordPress action
     * @param object $component A reference to the instance of the object on which the action is defined
     * @param string $callback The name of the function definition on the component
     * @param int $priority The priority at which the function should be fired
     * @param int $acceptedArgs The number of arguments that should be passed to the callback
     * @return void
     */
    public function addAction(
        string $hook,
        object $component,
        string $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add a new filter to the collection
     *
     * @param string $hook The name of the WordPress filter
     * @param object $component A reference to the instance of the object on which the filter is defined
     * @param string $callback The name of the function definition on the component
     * @param int $priority The priority at which the function should be fired
     * @param int $acceptedArgs The number of arguments that should be passed to the callback
     * @return void
     */
    public function addFilter(
        string $hook,
        object $component,
        string $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    /**
     * Utility function to register hooks
     *
     * @param array $hooks The collection of hooks
     * @param string $hook The name of the WordPress filter or action
     * @param object $component A reference to the instance of the object
     * @param string $callback The name of the function definition
     * @param int $priority The priority at which the function should be fired
     * @param int $acceptedArgs The number of arguments
     * @return array The updated hooks collection
     */
    private function add(
        array $hooks,
        string $hook,
        object $component,
        string $callback,
        int $priority,
        int $acceptedArgs
    ): array {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
