<?php

/*
|--------------------------------------------------------------------------
| Virtual numbers maker
|--------------------------------------------------------------------------
|
| This class uses to create virtual table of set of numbers like: 00000->99999 | A000->Z999
| The table called `virtual_numbers_table`
| 
| Can be used like:
| $numbers = new Numbers; // OR
| $numbers = new Numbers("00000",'99999');
| $numbers = new Numbers("AA000",'ZZ999');
| $numbers->from("000")->to("999");
| $numbers->query()->get();
| $numbers->query()->whereBetween('10', '30')->get();
| $numbers->query()->where('number', '>', '40')->get();
| $numbers->form('10')->to('80')->query()->get();
|
| $numbers->storeInTable('numbers');
*/


namespace Girover\SequentNumbers;

use BadMethodCallException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Numbers{

    /**
     * @var from_number :The collection of numbers should start with this.
     **/ 
    protected $from_number = 0;
    /**
     * @var from_number_as_array :Split the start number to an array 
     */
    protected $from_number_as_array = [];
    /** 
     * @var to_number :End the set of numbers with this number.
     */
    protected $to_number  = 9;
    /** 
     *@var to_number_as_array :Split the last number to an array
     */
    protected $to_number_as_array   = [];

    protected $digits     = ['0', '1', '2', '3', '4', '5', '6' ,'7' ,'8', '9'];
    protected $smallChars = ['a', 'b', 'c', 'd', 'e', 'f', 'g' ,'h' ,'i', 'j', 'k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
    protected $bigChars   = ['A', 'B', 'C', 'D', 'E', 'F', 'G' ,'H' ,'I', 'J', 'K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    /**
     * Constructor
     * @param string $from :The first number in the set
     * @param string $to   :The last number in the set
     */
    public function __construct($from="0", $to="9") {

        $this->from_number = $from;
        $this->to_number = $to;
    }

    /**
     * Set the first number in the numbers' set
     *
     * @param string $from_number like: 10000, 'A0000', .....
     * @return Girover\SequentNumbers\VirtualNumbersMaker
     */
    public function fromNumber($from_number)
    {
        $this->from_number          = $from_number;
        $this->from_number_as_array = $this->splitFromNumber($from_number);
        
        return $this;
    }

    public function from($from_number)
    {
        return $this->fromNumber($from_number);
    }

    /**
     * Set the last number of the numbers' set
     *
     * @param string $to_number like: 99999, 'A9999', .....
     * @return Girover\SequentNumbers\VirtualNumbersMaker
     */
    public function toNumber($to_number)
    {
        $this->to_number        = $to_number;
        $this->to_number_as_array = $this->splitToNumber($to_number);
        return $this;
    }

    public function to($to_number)
    {
        return $this->toNumber($to_number);
    }

    /**
     * Set the first and last number in the numbers' set.
     *
     * @param string $from like: 0000, 'A000', .....
     * @param string $to like: 99999, 'Z9999', .....
     * @return Girover\SequentNumbers\VirtualNumbersMaker
     */
    public function between($from, $to)
    {
        $this->fromNumber($from);
        $this->toNumber($to);

        return $this;
    }

    /**
     * Convert the first number to array of chars. like: [0,0,0,0], ['A','0','0','0'], .....
     *
     * @param string the number to split to array
     */
    private function splitFromNumber($number)
    {
        $arr = str_split($number);
        for($i=1; $i<count($arr); $i++){
            if(in_array($arr[$i],$this->digits)){
                $arr[$i] = '0';
            }elseif(in_array($arr[$i],$this->bigChars)){
                $arr[$i] = 'A';
            }else{
                $arr[$i] = 'a';
            }
        }
        return $arr;
    }

    /**
     * Convert the last number to array of chars. like: [9,9,9,9], ['Z','9','9','9'], .....
     *
     * @param string the number to split to array
     */
    private function splitToNumber($number)
    {
        $arr = str_split($number);
        for($i=1; $i<count($arr); $i++){
            if(in_array($arr[$i],$this->digits)){
                $arr[$i] = '9';
            }elseif(in_array($arr[$i],$this->bigChars)){
                $arr[$i] = 'Z';
            }else{
                $arr[$i] = 'z';
            }
        }
        return $arr;
    }

    /**
     * Make the field name for the virtual numbers table
     * 
     * @return string
     */
    private function makeFieldName()
    {
        if(count($this->from_number_as_array) !== count($this->to_number_as_array)){
            return false;
        }

        $field = ' CONCAT(';
        for($i= count($this->from_number_as_array); $i>0; $i--){
            $field .='val'.$i.',';
        }

        $field = substr($field, 0, -1).') AS `number` ';

        return $field;
    }

    /**
     * Make virtual table with one field and value with one digit|char
     *
     * @param digit|char $form : like 1,2,"2","a","m"
     * @param digit|char $to   : like 1,2,"2","a","m"
     * @param digit|char $to   : like 1,2,"2","a","m"
     *
     * @return string like: (select "a" val5 unoin select "b" val5 ........union select "z" val5) table5
     */
    private function makeTableWithOneFieldOneChar($from, $to, $fieldNumber)
    {
        $table = '(';
        foreach (range($from, $to) as $number) {
            $table .= ' SELECT "'.$number.'" val'.$fieldNumber.' UNION ';
        }
        // remove last 'UNION' from the string
        $table = preg_replace('/\W\w+\s*(\W*)$/', '',$table);
        $table .= ') table'.$fieldNumber.' ';

        return $table;
    }

    /**
     * Cross join all 'TableWithOneFieldOneChar' to gether to make big table with a lot of numbers
     *
     * (select 1 val1 union select 2 val1) table1 CROSS JOIN (select 1 val2 union s...)table2 CROSS JOIN ....
     */
    private function crossJoinTables()
    {
        $val = 1;
        $crossJoin = '';

        for($i=count($this->from_number_as_array);$i>0;$i--){
            $crossJoin .= $this->makeTableWithOneFieldOneChar($this->from_number_as_array[$i-1],$this->to_number_as_array[$i-1],$val++);
            $crossJoin .= ' CROSS JOIN ';
        }
        // Remove the last 'CROSS JOIN' from the string
        return preg_replace( "/\s[a-z]+\s[a-z]+\s$/i", "", $crossJoin);
    }

    /**
     * Make the query that gets all numbers between 'from_number' and 'to_number'
     * 
     * @return string
     */
    public function sqlQuery()
    {
        $query  = 'SELECT '.$this->makeFieldName().' FROM ';
        $query .= $this->crossJoinTables();

        return $query;
    }

    /**
     * Table called `numbers` has one field called `number`
     * you can get the sql as string by: table()->getValue()
     *
     * @return \Illuminate\Database\Expression
     */
    public function table()
    {
        // return DB::raw(' (SELECT `virtual_numbers`.`number` FROM ('.$this->sqlQuery().') virtual_numbers WHERE `virtual_numbers`.`number` BETWEEN "'.$this->from_number.'" AND "'.$this->to_number.'" ORDER BY `virtual_numbers`.`number`) virtual_numbers_table ');
        return DB::raw(' (SELECT `virtual_numbers`.`number` FROM ('.$this->sqlQuery().') virtual_numbers WHERE `virtual_numbers`.`number` BETWEEN "'.$this->from_number.'" AND "'.$this->to_number.'" ORDER BY `virtual_numbers`.`number`) virtual_numbers_table ');
    }

    /**
     * Getting query builder for the numbers table
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return DB::table($this->table());
    }


    public function storeInTable($table_name)
    {
        if (! Schema::hasTable($table_name)) {           
            (new TableCreator)->name($table_name)->create();
        }
        
        try {
            DB::insert("INSERT INTO `".$table_name."` (`number`) SELECT `number` FROM ".$this->table()->getValue());
            return true;
        } catch (\Throwable $th) {
            throw new NumbersException($th->getMessage(), 1);                    
        }
    }
}
