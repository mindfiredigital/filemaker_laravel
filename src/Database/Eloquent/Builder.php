<?php namespace filemaker_laravel\Database\Eloquent;

use laravel_filemaker\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloBuilder;

class Builder extends EloBuilder
{
    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }
    
    public function testElo()
    {
        echo 'eloquent builder';
    }
}