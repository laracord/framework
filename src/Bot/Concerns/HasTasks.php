<?php

namespace Laracord\Bot\Concerns;

use InvalidArgumentException;
use Laracord\Bot\Hook;
use Laracord\Tasks\Task;

trait HasTasks
{
    /**
     * The registered tasks.
     */
    protected array $tasks = [];

    /**
     * Boot the bot tasks.
     */
    protected function bootTasks(): self
    {
        foreach ($this->tasks as $task) {
            if (! $task->isEnabled()) {
                continue;
            }

            $this->tasks[$task::class] = $task->boot();
        }

        $this->callHook(Hook::AFTER_TASKS_REGISTERED);

        return $this;
    }

    /**
     * Register a task.
     */
    public function registerTask(Task|string $task): self
    {
        if (is_string($task)) {
            $task = $task::make();
        }

        if (! is_subclass_of($task, Task::class)) {
            $class = $task::class;

            throw new InvalidArgumentException("Class [{$class}] is not a valid task.");
        }

        $this->tasks[$task::class] = $task;

        return $this;
    }

    /**
     * Register multiple tasks.
     */
    public function registerTasks(array $tasks): self
    {
        foreach ($tasks as $task) {
            $this->registerTask($task);
        }

        return $this;
    }

    /**
     * Discover tasks in a path.
     */
    public function discoverTasks(string $in, string $for): self
    {
        foreach ($this->discover(Task::class, $in, $for) as $task) {
            $this->registerTask($task);
        }

        return $this;
    }

    /**
     * Get the registered tasks.
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Get a registered task by name.
     */
    public function getTask(string $name): ?Task
    {
        return $this->tasks[$name] ?? collect($this->tasks)->first(fn (Task $task): bool => $task->getName() === $name);
    }
}
