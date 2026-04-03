<?php

namespace Encore\HasmanyExtra;

use Encore\Admin\Form;
use Encore\HasmanyExtra\Fields\HasMany;
use Illuminate\Support\ServiceProvider;

class HasmanyExtraServiceProvider extends ServiceProvider
{
    /**
     * @param HasmanyExtra $extension
     * @return void
     */
    public function boot(HasmanyExtra $extension)
    {
        if (!HasmanyExtra::boot()) {
            return;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'hasmany-extra');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/laravel-admin-ext/hasmany-extra')],
                'hasmany-extra'
            );
        }

        Form::extend('hasMany', HasMany::class);
        Form::extend('morphMany', HasMany::class);

        $this->app->booted(function () {
            HasmanyExtra::routes(__DIR__.'/../routes/web.php');
        });
    }
}
