<?php namespace filemaker_laravel\Database\Eloquent;

/*
 +--------------------------------------------------------------------
 |
 | File    : Model.php
 | Path    : ./src/Database/Eloquent/Model.php
 | Purpose : Contains required laravel model methods
 | Created : 9-Feb-2016
 | Author  : Lakin Mohapatra, Debabrata Patra
 | Company : Mindfire Solutions.
 +--------------------------------------------------------------------
 */

use Illuminate\Database\Eloquent\Model as BaseModel;
use laravel_filemaker\Database\Query\Builder as QueryBuilder;

/**
* It overrides methods of default laravel models
*
* @see BaseModel
*
*/
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

    /**
    * Gets table name for eloquent
    *
    * @param void
    * @return String
    */
    public function getTable()
    {
        return $this->getLayoutName();
    }

    /**
    * Gets layout name from filemaker databse
    *
    * @param void
    * @return String
    */
    public function getLayoutName()
    {
        return $this->layoutName;
    }

    /**
    * Sets layout name
    *
    * @param String $layout - Layout Name
    * @return void
    */
    public function setLayoutName($layout)
    {
        $this->layoutName = $layout;
    }

    /**
    * Get the table qualified key name.
    * @param void
    * @return string
    */
    public function getQualifiedKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @param void
     * @return laravel_filemaker\Database\Query\Builder
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
     * @param  laravel_filemaker\Database\Query\Builder $query
     * @return laravel_filemaker\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Save the model to the database.
     *
     * @param  array
     * @return Boolean/Message
     */
    public function save(array $options = [])
    {
        $attributes = $this->attributes;

        if ($this->exists) {
            $query = $this->newBaseQueryBuilder();
            $query->from = $this->getLayoutName();
            $dirty = $this->getDirty();

            $where = array(
                'column' => $this->getKeyName(),
                'value' => $attributes[$this->getKeyName()],
                'operator' => '==',
                'boolean' => 'and',
                'type' => 'Basic'
            );
            $query->wheres = [$where];
            return $query->update($dirty);
        }

        return $this->insert($attributes);
    }

    /**
     * Delete the model from the database.
     *
     * @param  void
     * @return Boolean/Message
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }
        if (! $this->exists) {
            return false;
        }

        $attributes = $this->attributes;
        $query = $this->newBaseQueryBuilder();
        $where = array(
            'column' => $this->getKeyName(),
            'value' => $attributes[$this->getKeyName()],
            'operator' => '==',
            'boolean' => 'and',
            'type' => 'Basic'
        );
        $query->from = $this->getLayoutName();
        return $query->delete([$where]);
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes()
    {
        $builder = $this->newEloquentBuilder(
            $this->newBaseQueryBuilder()
        );

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this)->with($this->with);
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function firstOrCreate(array $attributes)
    {
        $query = (new static)->newQueryWithoutScopes();

        foreach ($attributes as $attributeKey => $attributeValue) {
            $query->where($attributeKey, '==', $attributeValue);
        }

        if (! is_null($instance = $query->first())) {
            return $instance;
        }

        return static::insert($attributes);
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function firstOrNew(array $attributes)
    {
        // Avoid mass assignment error
        static::unguard();
        $query = (new static)->newQueryWithoutScopes();

        foreach ($attributes as $attributeKey => $attributeValue) {
            $query->where($attributeKey, '==', $attributeValue);
        }

        if (! is_null($instance = $query->first())) {
            return $instance;
        }
        return new static($attributes);

        /*$model = new static();
        foreach ($attributes as $attributeKey => $attributeValue) {
             $model->$attributeKey = $attributeValue;
        }

        return $model;*/
    }
}
