<?php namespace filemaker_laravel\Database;

use Illuminate\Database\Connection as BaseConnection;
use FileMaker;

class Connection extends BaseConnection
{
    /**
     * The Filemaker database handler.
     *
     * @var Object - Filemaker DB
     */
    protected $db;
    
    /**
     * The Filemaker connection handler.
     *
     * @var Object - Filemaker Databse connection handler
     */
    protected $connection;
    
    
}