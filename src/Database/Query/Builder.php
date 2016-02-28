<?php namespace laravel_filemaker\Database\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use FileMaker;

//use filemaker_laravel\Database\Connection;

class Builder extends BaseBuilder
{
    protected $fmConnection;

   /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '=='
    ];
    
    protected $fmFields;

   /**
   * Create a new query builder instance.
   *
   * @param  \Illuminate\Database\ConnectionInterface  $connection
   * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar
   * @param  \Illuminate\Database\Query\Processors\Processor  $processor
   * @return void
   */
    public function __construct(
        \Illuminate\Database\ConnectionInterface $connection,
        \Illuminate\Database\Query\Grammars\Grammar $grammar,
        \Illuminate\Database\Query\Processors\Processor $processor
    ) {
        parent::__construct($connection, $grammar, $processor);
        //set_error_handler(null);
       //set_exception_handler(null);
        $this->fmConnection = $this->getFMConnection();
    }

    protected function getFMConnection()
    {
        return $this->connection->getConnection();
    }

   /*public function update($attributes = array())
   {
        echo $this->from;
   }*/

    public function insert(array $values)
    {
        if (empty($values)) {
            return false;
        }

        $insertCommand = $this->fmConnection->newAddCommand($this->from);

        foreach ($values as $attributeName => $attributeValue) {
            $insertCommand->setField($attributeName, $attributeValue);
        }

        $results = $insertCommand->execute();

        if (FileMaker::isError($results)) {
            return false;
        }

        return $results->getRecords();
    }

    public function update(array $values)
    {
        if (empty($values)) {
            return false;
        }

    }

   /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        $records = array();
        if ($this->isOrCondition($this->wheres)) {
            
        } else {
            $findCommand = $this->fmConnection->newFindCommand($this->from);
            $this->addBasicFindCriterion($this->wheres, $findCommand);
            $results = $findCommand->execute();

            if (FileMaker::isError($results)) {
                return false;
            }

            return $this->getFMResult($columns, $results);
        }
    }

    protected function getFMResult($columns, $results = array())
    {
        if (empty($columns) || empty($results)) {
            return false;
        }
        
        $records = $results->getRecords();
        $this->fmFields = $results->getFields();
        
        foreach ($records as $record) {
            $eloquentRecords[] = $this->getFMFieldValues($record, $columns);
        }
        
        return $eloquentRecords;
    }

    protected function getFMFieldValues($fmRecord = array(), $columns = '')
    {
        if (empty($fmRecord) || empty($columns)) {
            return false;
        }
        
        if (in_array('*', $columns)) {
            $columns = $this->fmFields;
        }
        
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $eloquentRecord[$column] = $this->getIndivisualFieldValues($fmRecord, $column);
            }
        } elseif (is_string($columns)) {
            $eloquentRecord[$columns] = $this->getIndivisualFieldValues($fmRecord, $columns);
        }
        
        return $eloquentRecord;
    }
    
    protected function getIndivisualFieldValues($fmRecord, $column) {
        return in_array($column, $this->fmFields)
               ? $fmRecord->getField($column)
               : '';
    }

    protected function addBasicFindCriterion($wheres = array(), $findCommand = array())
    {
        if (empty($wheres) || empty($findCommand)) {
            return false;
        }

        foreach ($wheres as $where) {
            $findCommand->addFindCriterion(
                $where['column'],
                $where['operator'] . $where['value']
            );
        }
    }

   /**
   * Used to check for and/or condition
   *
   */
    protected function isOrCondition($wheres = array())
    {
        if (empty($wheres)) {
            return false;
        }

        return in_array('or', array_pluck($wheres, 'boolean'));

    }
}
