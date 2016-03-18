<?php namespace filemaker_laravel\Database;

/**
 +--------------------------------------------------------------------
 |
 | File    : Connection.php
 | Path    : ./src/Database/Connection.php
 | Purpose : Contains all methods of filemaker connections
 | Created : 9-Feb-2016
 | Author  : Lakin Mohapatra, Debabrata Patra
 | Company : Mindfire Solutions.
 | Comments:
 +--------------------------------------------------------------------
 */

use Illuminate\Database\Connection as BaseConnection;
use FileMaker;

/**
 * It manages the filemaker connection.
 *
 * @see \Illuminate\Database\Connection
 */
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

    /**
     * Create a new database connection instance.
     *
     * @param  Array   $config -hostname, username, password & database
     * @return void
     */
    public function __construct(array $config)
    {
        $this->useDefaultQueryGrammar();
        $this->useDefaultPostProcessor();
        $this->config = $config;
    }

    /**
    * Gets filemaker connection Object
    *
    * @param void
    * @return Object - FileMaker Connection Object
    */
    public function getConnection()
    {
        $config = $this->config;
        return $this->createFMConnection($config);
    }

    /**
    * Creates filemaker connection
    *
    * @param Array $config - Array containing following indexes.
    *                             - database
    *                             - host
    *                             - username
    *                             - password
    */
    public static function createFMConnection($config)
    {
        return new FileMaker(
            $config['database'],
            $config['host'],
            $config['username'],
            $config['password']
        );
    }
}
