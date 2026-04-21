<?php

namespace Laracord\Bot\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUserModel
{
    /**
     * The user model class.
     */
    protected ?string $userModel = null;

    /**
     * Set the user model class.
     */
    public function withUserModel(string $model): self
    {
        if (! is_subclass_of($model, Model::class)) {
            throw new Exception('The user model must extend '.Model::class);
        }

        $this->userModel = $model;

        return $this;
    }

    /**
     * Get the user model class.
     */
    public function getUserModel(): ?string
    {
        if ($this->userModel) {
            return $this->userModel;
        }

        $model = Str::start($this->app->getNamespace(), '\\').'Models\\User';

        if (! class_exists($model)) {
            return null;
        }

        if (! is_subclass_of($model, Model::class)) {
            throw new Exception('The user model must extend '.Model::class);
        }

        return $this->userModel = $model;
    }
}
