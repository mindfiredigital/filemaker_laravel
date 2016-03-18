<?php namespace filemaker_laravel\Database;

/**
 +--------------------------------------------------------------------
 |
 | File    : FileMakerServiceProvider.php
 | Path    : ./src/Database/FileMakerServiceProvider.php
 | Purpose : Contains all methods of filemaker connections
 | Created : 9-Feb-2016
 | Author  : Lakin Mohapatra, Debabrata Patra
 | Company : Mindfire Solutions.
 | Comments:
 +--------------------------------------------------------------------
 */

use filemaker_laravel\Database\Connection;
use Illuminate\Support\ServiceProvider;

/**
* Creates serviceprovider for filemaker
*
* @see \Illuminate\Support\ServiceProvider
*
*/
class FileMakerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @param void
     * @return void
     */
    public function register()
    {
        $this->app->resolving('db', function ($db) {

            $db->extend('filemaker', function ($config) {

                return new Connection($config);
            });
        });
    }
}
