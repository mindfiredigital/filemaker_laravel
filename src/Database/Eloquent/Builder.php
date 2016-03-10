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