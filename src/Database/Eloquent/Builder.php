<?php namespace filemaker_laravel\Database\Eloquent;

/**
+---------------------------------------------------------
| File    : Builder.php
| Path    : ./src/Database/Eloquent/Builder.php
| Purpose : Contains laravel eloquent builder methods
| Created : 9-Feb-2016
| Author  : Lakin Mohapatra, Debabrata Patra
| Company : Mindfire Solutions.
+---------------------------------------------------------
*/

use laravel_filemaker\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloBuilder;

/**
* Used to override methods of Eloquent builder
*
*  @see EloBuilder
*/
class Builder extends EloBuilder
{
    /**
    * Create a new Eloquent query builder instance.
    *
    * @param  laravel_filemaker\Database\Query\Builder  $query
    * @return void
    */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
    * Find a model by its primary key.
    *
    * @param  array  $ids
    * @param  array  $columns
    * @return \Illuminate\Database\Eloquent\Collection
    */
    public function findMany($ids, $columns = ['*'])
    {
        if (empty($ids)) {
            return $this->model->newCollection();
        }

        foreach ($ids as $id) {
            $where = array(
                'column' => $this->model->getQualifiedKeyName(),
                'value' => $id,
                'operator' => '==',
                'boolean' => 'or',
                'type' => 'Basic'
            );
            $this->query->wheres[] = $where;
        }

        return $this->get($columns);
    }
}
