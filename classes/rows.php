<?php

class Rows implements ArrayAccess, Countable, Stringable, Iterator{

    protected $pageSize = 100;

    protected $table = "row";
    protected $rows = null;
    protected $filters = null;
    protected $rowClass = 'Row';
    private $count = -1;
    private $curPage = -1;
    private $iteratorIndex = 0;
    
    public function __construct($filters = null){
        if (uStr::len(static::class) > 5 && uStr::starts_with(static::class, "rows_", false)){
            $this->table = uStr::toLower(Database::escape(uStr::substr(static::class, 5)));
            $this->rowClass = "Row_{$this->table}";
        }

        if (is_array($filters) || $filters instanceof traversable){
            $this->filters = $filters;
        }
    }

    protected function calcWhere(){
        $query = '';
        if (!is_null($this->filters)){
            foreach ($this->filters as $col=> $val){
                $col = Database::escape($col);
                $val = Database::escape($val);
                $query = empty($query) ? '' : "{$query} AND ";
                $query .= "`{$col}` = '{$val}'";
            }
        }
        $query = empty($query) ? '' : " WHERE {$query}";
        return $query;
    }
    
    private function prepare(){
        if (0 > $this->count){
            $count = 0;
            $query = $this->calcWhere();
            $query = "SELECT COUNT(`id`) AS `count` FROM `{$this->table}`{$query};";
            $query = Database::dbr()->query($query, PDO::FETCH_NUM)->fetchAll();
            if (is_array($query) && 0 < count($query)){
                $count = $query[0][0];
            }
            $this->count = $count;

            $this->curPage = -1;
            $this->rows = null;
        }
    }

    private function pageToIndex($offset){
        if ($this->offsetExists($offset)){
            $reqPage = intdiv($offset, $this->pageSize);
            $startIndex = $reqPage * $this->pageSize;
            if ($this->curPage != $reqPage || null == $this->rows){
                $query = $this->calcWhere();
                $query = "SELECT * FROM `{$this->table}`{$query} ORDER BY `id` LIMIT {$this->pageSize} OFFSET {$startIndex};";
                $query = Database::dbr()->query($query, PDO::FETCH_ASSOC)->fetchAll();
                if (is_array($query)){
                    $this->rows = $query;
                    $this->curPage = $reqPage;
                }
            }
        }
    }
    
    //****Countable Interface
    public function count(): int{
        $this->prepare();
        return $this->count;
    }

    //****Array Access Interface
    public function offsetExists($offset): bool {
        $this->prepare();
        return $this->count > $offset ? true : false;
    }

    public function offsetGet($offset): mixed{
        $output = null;
        $this->prepare();
        if ($this->offsetExists($offset)){
            $this->pageToIndex($offset);
            $iIndex = $offset % $this->pageSize;
            $output = $this->rows[$iIndex];
            if (is_array($output)){
                $output = new $this->rowClass($output['id']);
                $this->rows[$iIndex] = $output;
            }
        }
        return $output;
    }

    public function offsetSet($offset, $value): void{ } //What should this one do? Not implmenting for now.
                                                  
    public function offsetUnSet($offset): void{ } // What should this one do? Not implmenting for now. }

    //Iterator Interface
    public function current(): mixed{
        return $this[$this->iteratorIndex];
    }

    public function key() : mixed{
        return $this[$this->iteratorIndex]['id'];
    }

    public function next(): void{
        $this->iteratorIndex++;
    }

    public function rewind(): void{
        $this->iteratorIndex = 0;
    }

    public function valid(): bool{
        return $this->iteratorIndex < $this->count();
    }

    //Stringable Interface
    public function __toString(): string{
        $escape = function($in){
            $out = addcslashes($in, '\\\'');
            return $out;
        };
        $indent = 0;
        if (1 < func_num_args()){
            $arg = func_get_arg(1);
            if (is_numeric($arg)){
                $indent = $arg;
            }
        }
        $indent = str_repeat("\t", $indent);
        $output = "{$indent}{\n";
        $output .= "{$indent}\t\"table\": \"" . $escape($this->table) . "\",\n";
        $output .= "{$indent}\t\"filter\": " . UStr::jsonify($this->filters, 1) . ",\n";
        $output .= "{$indent}\t\"rows\": " . UStr::jsonify($this, 1) . "\n";
        $output .= "{$indent}}\n";
        return $output;
    }
        
}
?>
