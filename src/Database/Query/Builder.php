<?php namespace laravel_filemaker\Database\Query;

/*
 +--------------------------------------------------------------------
 |
 | File    : Builder.php
 | Path    : ./src/Database/Query/Builder.php
 | Purpose : Contains all methods of laravel query builder.
 | Created : 9-Feb-2016
 | Author  : Lakin Mohapatra, Debabrata Patra
 | Company : Mindfire Solutions.
 | Comments:
 +--------------------------------------------------------------------
 */

use Illuminate\Database\Query\Builder as BaseBuilder;
use FileMaker;
use Log;

/**
* Used to override methods of laravel query builder.
*
* @see BaseBuilder
*
*/
class Builder extends BaseBuilder
{
    /**
    * It contains filemaker connection object.
    *
    */
    protected $fmConnection;

   /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '=='
    ];


    /**
     * It contains fields to be returned.
     *
     * @var array
     */
    protected $fmFields;

    /**
     * It contains fields to be returned.
     *
     * @var array
     */
    protected $fmScript;

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
        $this->fmConnection = $this->getFMConnection();
    }

    /**
     * Gets Filemaker Connection Object
     *
     * @param void
     * @return FileMaker Connection Object
     */
    protected function getFMConnection()
    {
        return $this->connection->getConnection();
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values - Values to insert
     * @return bool
     */
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
            Log::error($results->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Update record(s) into the database.
     *
     * @param  array  $columns - Coulmns to update
     * @return bool
     */
    public function update(array $columns)
    {
        // Get find results
        $results = $this->getFindResults($columns);

        if (FileMaker::isError($results)) {
            Log::error($results->getMessage());
            return false;
        }

        $records = $results->getRecords();

        //Loop through each record for mass update
        foreach ($records as $record) {
            // Get record id
            $recordId = $record->getRecordId();

            // Use new edit command
            $command = $this->fmConnection->newEditCommand($this->from, $recordId);

            // Set fields
            foreach ($columns as $key => $value) {
                $command->setField($key, $value);
            }

            $results = $command->execute();

            if (FileMaker::isError($results)) {
                Log::error($results->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Delete a record from the database.
     *
     * @param  array  $attribute
     * @return bool
     */
    public function delete($attribute = null)
    {
        if (!is_null($attribute)) {
            $this->wheres = $attribute;
        }

        // Make a new find request.
        $command = $this->fmConnection->newFindCommand($this->from);
        $this->addBasicFindCriterion($this->wheres, $command);

        $results = $command->execute();

        if (FileMaker::isError($results)) {
            Log::error($results->getMessage());
            return false;
        }

        $records = $results->getRecords();
        foreach ($records as $record) {
            $record->delete();
        }

        return true;
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
            Log::error($results->getMessage());
            return array();
        }

        return $this->getFMResult($columns, $results);
    }

    /**
     * Delete a record from the database.
     *
     * @param  array  $columns
     * @return bool
     */
    protected function getFindResults($columns = [])
    {
        // Check for Fm script.
        if (property_exists($this, 'fmScript') && ! empty($this->fmScript)) {
            return $this->executeFMScript($this->fmScript);
        }

        if ($this->isOrCondition($this->wheres)) {
            $command = $this->compoundFind($columns);
        } else {
            $command = $this->basicFind();
        }

        // OrderBy/Limit feature for filemaker
        $this->fmOrderBy($command);
        $this->setRange($command);

        return $command->execute();
    }

    /**
     * FileMaker And operation using newFindCommand
     *
     * @param void
     * @return FileMaker Command
     */
    protected function basicFind()
    {
        $command = $this->fmConnection->newFindCommand($this->from);
        $this->addBasicFindCriterion($this->wheres, $command);

        return $command;
    }

    /**
     * FileMaker OR operation using newFindRequest
     *
     * @param void
     * @return FileMaker Command
     */
    protected function compoundFind()
    {
        //Separate "and" and "or" clause and put them in separate arrays
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

    /**
     * Add a FileMaker "order by" clause to the query.
     *
     * @param FileMaker Command
     * @return void
     */
    public function fmOrderBy($command)
    {
        $i = 1;
        foreach ($this->orders as $order) {
            $direction = $order['direction'] == 'desc'
                         ? FILEMAKER_SORT_DESCEND
                         : FILEMAKER_SORT_ASCEND;
            $command->addSortRule($order['column'], $i, $direction);
            $i++;
        }
    }

    /**
     * Add "Offset/start" clause to the query.
     *
     * @param Integer $offset - offset value
     * @return \filemaker_laravel\Database\Query\Builder|static
     */
    public function skip($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add "Offset/start" clause to the query.
     *
     * @param Integer $limit - limit for no. of records
     * @return \filemaker_laravel\Database\Query\Builder|static
     */
    public function limit($limit)
    {

        $this->limit = $limit;
        return $this;
    }

    /**
     * Set range to the query.
     *
     * @param Object $command - FileMaker Command
     * @return \filemaker_laravel\Database\Query\Builder|static
     */
    public function setRange($command)
    {
        $command->setRange($this->offset, $this->limit);
        return $this;
    }

    /**
     * Get formatted filemaker result
     *
     * @param array/string $columns - Field names
     * @param Array $results - FileMakerResult Object
     * @return Array
     */
    protected function getFMResult($columns, $results = array())
    {
        $eloquentRecords = array();

        if (empty($columns) || empty($results)) {
            return $eloquentRecords;
        }

        $records = $results->getRecords();
        $this->fmFields = $results->getFields();
        $this->relatedSets = $results->getRelatedSets();


        foreach ($records as $record) {
            $eloquentRecords[] = $this->getFMFieldValues($record, $columns);
        }

        return $eloquentRecords;
    }

    /**
     * Make an array of fields.
     *
     * @param array $fmRecord - FileMakerRecord Object
     * @param array/string $columns - Field names
     * @return array
     */
    protected function getFMFieldValues($fmRecord = array(), $columns = '')
    {
        $eloquentRecord = array();

        //Check for empty values
        if (empty($fmRecord) || empty($columns) || empty($this->relatedSets)) {
            return $eloquentRecord;
        }

        // Assign all fields
        if (in_array('*', $columns)) {
            $columns = $this->fmFields;
        }

        // Assign indivisual fields
        if (is_string($columns)) {
            $columns = [$columns];
        }

        // Get field-value pairs
        foreach ($columns as $column) {
            $eloquentRecord[$column] = $this->getIndivisualFieldValues($fmRecord, $column, $this->fmFields);
        }

        //Check if there is any portal
        foreach ($this->relatedSets as $relatedSet) {
            // Get related set.
            $relatedSetObj = $fmRecord->getRelatedSet($relatedSet);

            // Check for error
            if (!FileMaker::isError($relatedSetObj)) {
                $relatedSetfields = $relatedSetObj[0]->getFields();

                // Get relatedset field-value pair
                foreach ($relatedSetfields as $relatedSetField) {
                    foreach ($relatedSetObj as $relatedSetRecord) {
                        $eloquentRecord[$relatedSetField][] = $this->getIndivisualFieldValues(
                            $relatedSetRecord,
                            $relatedSetField,
                            $relatedSetfields
                        );
                    }
                }
            }
        }

        return $eloquentRecord;
    }

    /**
     * Returns value of FileMaker field
     *
     * @param array $fmRecord - FileMaker Record Object
     * @param array/string $field - Field name
     * @return integer/string $totalFields - Array containing all fields
     */
    protected function getIndivisualFieldValues($fmRecord, $field, $totalFields)
    {
        return in_array($field, $totalFields)
               ? $fmRecord->getField($field)
               : '';
    }

    /**
     * Add FileMaker Criterions for And/Or operations
     *
     * @param array $wheres - Where conditions
     * @param array $findCommand - FindCommandObject
     * @return void
     */
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

    /**
     * Add FileMaker newCompundFindCommand for OR operations
     *
     * @param array $orColumns - Array containing Or columns
     * @param array $andColumns - Array containing and columns
     * @return FileMaker Command
     */
    protected function newFindRequest($orColumns, $andColumns)
    {
        $findRequests = array();

        // Make find request for each or column
        foreach ($orColumns as $orColumn) {
            $findRequest =  $this->fmConnection->newFindRequest($this->from);
            $this->addBasicFindCriterion([$orColumn], $findRequest);
            $this->addBasicFindCriterion($andColumns, $findRequest);
            $findRequests[] = $findRequest;
        }

        $compoundFind = $this->fmConnection->newCompoundFindCommand($this->from);
        $i = 1;

        // Combine all find requests with compound find
        foreach ($findRequests as $findRequest) {
            $compoundFind->add($i, $findRequest);
            $i++;
        }

        return $compoundFind;
    }

    /**
    * Used to check for and/or condition
    *
    * @param array $wheres - Where conditions in one array
    * @return bool
    */
    protected function isOrCondition($wheres = array())
    {
        if (empty($wheres)) {
            return false;
        }

        return in_array('or', array_pluck($wheres, 'boolean'));
    }

    /**
    * Execute FileMaker script
    *
    * @param array $scriptAttributes - Attributes for filemaker script execution
    * @return FileMaker Result
    */
    public function executeFMScript($scriptAttributes = array())
    {
        $results = array();

        $scriptCommand = $this->fmConnection
                              ->newPerformScriptCommand(
                                  $this->from,
                                  $scriptAttributes['scriptName'],
                                  $scriptAttributes['params']
                              );
        return $scriptCommand->execute();
    }

    /**
    * set name and params in an array
    *
    * @param string $scriptName - Name of script
    * @param string $params - Prameter required for filemaker script
    * @return void
    */
    public function performScript($scriptName = '', $params = '')
    {
        $this->fmScript = array(
            'scriptName' => $scriptName,
            'params' => $params
        );
    }
}
