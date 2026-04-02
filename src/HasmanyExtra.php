<?php

namespace Encore\HasmanyExtra;

use Encore\Admin\Extension;

class HasmanyExtra extends Extension
{
    /**
     * @var string
     */
    public $name = 'hasmany-extra';

    /**
     * @var string
     */
    public $views = __DIR__.'/../resources/views';

    /**
     * @var string
     */
    public $assets = __DIR__.'/../resources/assets';
}
