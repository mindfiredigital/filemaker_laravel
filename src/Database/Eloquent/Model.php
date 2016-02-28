<?php namespace filemaker_laravel\Database\Eloquent;
/*
 +--------------------------------------------------------------------
 |
 | File    : Model.php
 | Path    : ./src/Database/Eloquent/Model.php
 | Purpose : Contains all soap api connection methods for magento.
 | Created : 9-Feb-2016
 | Author  : Lakin Mohapatra, Debabrata Patra
 | Company : Mindfire Solutions.
 | Comments:
 | Last Modified Date : 9-Feb-2016
 |
 +--------------------------------------------------------------------
 */

use Illuminate\Database\Eloquent\Model as BaseModel;
use laravel_filemaker\Database\Query\Builder as QueryBuilder;

abstract class Model extends BaseModel
{
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        set_error_handler(null);
        set_exception_handler(null);
    }
    
    public function getTable()
    {
        return $this->getLayoutName();
    }
    
    public function getLayoutName()
    {
        return $this->layoutName;
    }

    public function setLayoutName($layout)
    {
        $this->layoutName = $layout;
    }
    
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }
    
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }
    
    public function save(array $options = [])
    {
        $attributes = $this->attributes;
       
         if ($this->exists) {
            return $this->update($attributes);
        }
        
        return $this->insert($attributes);
    }
    
}