<?php

abstract class Rows implements ArrayAccess, Countable, Stringable, Iterator{

	protected $pageSize = 100;

	protected $table = "row";
	protected $rows = null;
	protected $filters = null;
	protected $sorts = null;
	protected $rowClass = 'Row';
	private $count = -1;
	private $curPage = -1;
	private $iteratorIndex = 0;
	private $requireGroupBy = false;
	
	// Use to indicate a comparison with NULL vs the string 'NULL' actual PHP null will be a no-op
	
	public function __construct($filters = null, $sorts = null){
		if (uStr::len(static::class) > 5 && uStr::starts_with(static::class, "rows_", false)){
			$this->table = uStr::toLower(Database::escape(uStr::substr(static::class, 5)));
			$this->rowClass = "Row_{$this->table}";
		}

		if (is_array($filters) || $filters instanceof traversable){
			$this->filters = $filters;
		}

		if (is_array($sorts) || $sorts instanceof traversable){
			if (array_is_list($sorts))
			{
				$fullSorts = array();
				foreach ($sorts AS $field)
				{
					$fullSorts[$field] = null;
				}
				$sorts = $fullSorts;
			}
			$this->sorts = $sorts;
		}
	}

	protected function calcWhere(){
		$query = '';
		if (!is_null($this->filters)){
			foreach ($this->filters as $col=> $val){
				$col = Database::escape($col);
				$comp = UStr::substr($col, 0, 1);
				switch ($comp){
					case '=':
					case '!':
					case '>':
					case '<':
						if (1 < UStr::len($col) && '=' == UStr::substr($col, 1, 1)){
							$comp .= '=';
							$col = UStr::substr($col, 2);
						} else {
							$col = UStr::substr($col, 1);
						}
						break;
					default:
						$comp = '=';
						break;
				}
				switch ($comp){
					case '==':
						$comp = '=';
						break;
					case '!':
						$comp = '!=';
						break;
				}
				$lastDot = UStr::strrpos($col, '.');
				if (false === $lastDot){
					$col = "`{$this->table}`.`{$col}`";
				} else {
					$col = "`" . UStr::substr($col, 0, $lastDot) . "`.`" . UStr::substr($col, $lastDot+1) . "`";
					$this->requireGroupBy = true;
				}
				$val = is_null($val) ? null : Database::escape($val);
				$query = empty($query) ? "\t{$col}" : "{$query}\n\tAND {$col}";
				if (is_null($val)){
					switch($comp){
						case '!=':
						case '>':
							$query .= ' IS NOT NULL';
							break;
						default:
							$query .= ' IS NULL';
							break;
					}
				} else {
					$query .= " {$comp} '{$val}'";
				}
			}
		}
		$query = empty($query) ? '' : " WHERE\n{$query}";
		return $query;
	}
    
	protected function calcSort(){
		$query = '';
		if (!is_null($this->sorts)){
			foreach ($this->sorts as $col){
				$col = Database::escape($col);
				$lastDot = UStr::strrpos($col, '.');
				if (false === $lastDot){
					$col = "`{$this->table}`.`{$col}`";
				} else {
					$col = "`" . UStr::replace('.', '_', UStr::substr($col, 0, $lastDot)) . "`.`" . UStr::substr($col, $lastDot+1) . "`";
				}
				$query = empty($query) ? "\t{$col}" : "{$query}\n\tAND {$col}";
				if (is_null($val)){
					switch($comp){
						case '!=':
						case '>':
							$query .= ' IS NOT NULL';
							break;
						default:
							$query .= ' IS NULL';
							break;
					}
				} else {
					$query .= " {$comp} '{$val}'";
				}
			}
		}
		$query = empty($query) ? '' : " WHERE\n{$query}";
		return $query;
	}
    
	protected function calcJoins($indent = 0){
		$output = '';
		$arJoins = array();
		$joinKeys = array_keys($this->filters);
		foreach ($joinKeys as $key){
			$col = $key;
			switch (UStr::substr($col, 0, 1)){
				case '=':
				case '!':
				case '>':
				case '<':
					if (1 < UStr::len($col) && '=' == UStr::substr($col, 1, 1)){
						$col = UStr::substr($col, 2);
					} else {
						$col = UStr::substr($col, 1);
					}
			}
			if (str_contains($col, '.')){
				$lastTable = $this->table;
				$arCol = explode('.', $col);
				$alias = '';
				for ($i=0; $i < count($arCol)-1; $i++){
					$joinTable = $arCol[$i];
					$joinTable = uStr::toLower(Database::escape($joinTable));
					$lastAlias = empty($alias) ? $this->table : $alias;
					$alias .= empty($alias) ? '' : '_';
					$alias .= $joinTable;
					if (!array_key_exists($alias, $arJoins)){
						$arJoins[$alias] = "JOIN `{$joinTable}` AS `{$alias}` ON `{$alias}`.`{$lastTable}_id` = `{$lastAlias}`.`id`";
					}
					$lastTable = $joinTable;
				}
			}
		}
		if (0 < count($arJoins)){
			$indent = str_repeat("\t", $indent);
			$output = $indent . implode ("\n".$indent, $arJoins) . "\n";
		}
		return $output;
	}

	private function prepare(){
		if (0 > $this->count){
			$count = 0;
			$joins = $this->calcJoins(1);
			$query = empty($joins) ? "`{$this->table}`.`id`" : "DISTINCT(`{$this->table}`.`id`)";
			$query = "SELECT\n\tCOUNT({$query}) AS `count`\nFROM\n\t`{$this->table}`\n";
			$query .= $joins;
			$query .= $this->calcWhere();
			$query .= ';';
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
				$joins = $this->calcJoins(1);
				$query = "SELECT\n\t`{$this->table}`.*\nFROM\n\t`{$this->table}`\n{$joins}{$query}\nORDER BY\n\t`{$this->table}`.`id`\nLIMIT {$this->pageSize}\nOFFSET {$startIndex};";
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
