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
    
    /**
     * Create a new database connection instance.
     *
     * @param  array   $config
     */
    public function __construct(array $config)
    {
        $this->useDefaultQueryGrammar();
		$this->useDefaultPostProcessor();
		$this->config = $config;
    }
    
    public function getConnection()
	{
        $config = $this->config;
        return $this->createFMConnection($config);
    }
    
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