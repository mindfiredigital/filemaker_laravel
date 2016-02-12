<?php namespace laravel_filemaker\Database\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    public function test()
    {
        echo 'testBuilder';
    }
}