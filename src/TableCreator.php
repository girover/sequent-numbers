<?php

namespace Girover\SequentNumbers;

use Girover\SequentNumbers\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class TableCreator{

    /**
     * @var $table the name of the table to create.
     */
    protected $table;
    
    /**
     * @var $name the name of the table to create.
     */
    protected $name = 'numbers';
    
    /**
     * Set the name of the table
     * 
     * @param string $name
     * @return \Girover\SequentNumbers\TableMaker
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Creating the table in the database with the a specified name
     * 
     */
    public function create()
    {
        // No name for the table is provided
        if (is_null($this->name)) {
            return false;
        }

        // The table is already exists in database
        if (Schema::hasTable($this->name)) {
            throw new NumbersException("Table ( ".$this->name." ) already exists in database.", 1);
        }

        Schema::create($this->name, function($table){
            // $table->increments('id');
            $table->string('number')->unique();

            // $table->unique('number');
            $table->index('number');
        });

        return true;
    }

    public function fill()
    {
        // Table is not created yet
        if (!Schema::hasTable($this->name)) {
            return 0;
        }

        $numbersTable = new Numbers($this->game->first_card, $this->game->last_card);
        
        try {
            DB::insert("INSERT INTO `".$this->name."` (`number`) SELECT `number` FROM ".$numbersTable->table()->getValue());
            return 1;
        } catch (\Illuminate\Database\QueryException $th) {
            return -1;
        }

        return 1;
    }

    public function fillWithNumbers(string $from, string $to)
    {
        $numbers_table = new Numbers($from, $to);
        
        return $this->fill($numbers_table);
    }

    public function drop()
    {
        Schema::drop($this->name);
        return true;
    }

}
