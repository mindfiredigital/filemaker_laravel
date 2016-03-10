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
    protected $fmResult;

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

    public function update(array $columns)
    {
        $result = $this->getFindResults($columns);
        $msg = 'false';
        if(!FileMaker::isError($result) && $result->getFetchCount() > 0) {
            $records = $result->getRecords();
            foreach($records as $record) {
                $recordId = $record->getRecordId();

                $command = $this->fmConnection->newEditCommand($this->from, $recordId);
                if(FileMaker::isError($command)){
                    return $msg;
                }
                foreach($columns as $key => $value) {
                    if ($key != 'updated_at') {
                        $command->setField($key, $value);
                    }
                }
                $result = $command->execute();
                if (!FileMaker::isError($result)){
                    $msg = 'true';
                }
                else {
                    $msg = $result->getMessage();
                }
            }
        }
        else {
            $msg = $result->getMessage();
        }
        return $msg;
    }

    public function delete($attribute = null) {

        if(!is_null($attribute)) {
            $this->wheres = $attribute;
        }
        $command = $this->fmConnection->newFindCommand($this->from);
        $this->addBasicFindCriterion($this->wheres, $command);

        $result = $command->execute();

        if(!FileMaker::isError($result) && $result->getFetchCount() > 0) {
            $records = $result->getRecords();
            foreach($records as $record) {
                $record->delete();
            }
        }
        else {
            $msg = $result->getMessage();
            echo $msg;
        }
        return $msg;
    }

   /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        $results = $this->getFindResults();

        if (FileMaker::isError($results)) {
            //echo $results->getMessage();
                return array();
        }

        $this->fmResult = $this->getFMResult($columns, $results);

        return $this->fmResult;
    }

    protected function getFindResults($columns) {
        if ($this->isOrCondition($this->wheres)) {
            $command = $this->compoundFind($columns);
        } else {
            $command = $this->basicFind();
        }

        $this->fmOrderBy($command);
        $this->setRange($command);

        return $command->execute();
    }

    protected function basicFind() {
        $command = $this->fmConnection->newFindCommand($this->from);
        $this->addBasicFindCriterion($this->wheres, $command);

        return $command;
    }

    public function fmOrderBy($command)
    {
        $i = 1;
        foreach($this->orders as $order) {
            $direction = $order['direction'] == 'desc'
                         ? FILEMAKER_SORT_DESCEND
                         : FILEMAKER_SORT_ASCEND;
            $command->addSortRule($order['column'], $i, $direction);
            $i++;
        }
    }

    public function skip($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function limit($limit)
    {

        $this->limit = $limit;
        return $this;
    }

    public function setRange($command)
    {
        $command->setRange($this->offset, $this->limit);
        return $this;
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
            $operator = $where['operator'] === '=' ? '==' : $where['operator'];

            $findCommand->addFindCriterion(
                $where['column'],
                $operator . $where['value']
            );
        }
    }

    protected function compoundFind()
    {
        $orColumns = array();
        $andColumns = array();

        foreach ($this->wheres as $where) {
            $eloquentBoolean = strtolower($where['boolean']);
            if ($eloquentBoolean === 'or') {
                $orColumns[] = $where;
            } elseif ($eloquentBoolean === 'and') {
                $andColumns[] = $where;
            }
        }

        return $this->newFindRequest($orColumns, $andColumns);
    }

    protected function newFindRequest($orColumns, $andColumns)
    {
        $findRequests = array();
        foreach ($orColumns as $orColumn) {
            $findRequest =  $this->fmConnection->newFindRequest($this->from);
            $this->addBasicFindCriterion([$orColumn], $findRequest);
            $this->addBasicFindCriterion($andColumns, $findRequest);
            $findRequests[] = $findRequest;
        }

        $compoundFind = $this->fmConnection->newCompoundFindCommand($this->from);
        $i = 1;
        foreach ($findRequests as $findRequest) {
            $compoundFind->add($i, $findRequest);
            $i++;
        }

        return $compoundFind;
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
