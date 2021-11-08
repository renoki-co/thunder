<?php

namespace RenokiCo\Thunder;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RenokiCo\Thunder\ThunderManager
 */
class Thunder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'thunder.manager';
    }
}
