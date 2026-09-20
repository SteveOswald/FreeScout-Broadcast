<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
|
| When a module starting, this file will executed automatically. This helps
| to register some namespaces like translator or view. Also this file
| will load the routes file for each module.
|
*/

if (!app()->routesAreCached()) {
    require __DIR__.'/Http/routes.php';
}
