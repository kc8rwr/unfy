<?php

class Row implements ArrayAccess, Iterator, Countable, Stringable{

	protected $table = 'row';
	private $dbValues = null;
	private $wrkValues = array();
	private $iteratorIndex = 0;
	private $children = null;
    
	function __construct($id){
		if (uStr::len(static::class) > 4 && uStr::starts_with(static::class, "row_", false)){
			$this->table = uStr::toLower(Database::escape(uStr::substr(static::class, 4)));
		}

		if (null != $id){
			if (is_numeric($id)){
				$this->dbValues = array('id'=> $id);
			} else {
				if (is_object($id) && (!is_array($id))){
					$id = (array)$id;
				}                
				if (is_array($id)){
					if (array_key_exists('id', $id)){
						$this->dbValues = $id;
					}
				}
			}
		}
	}

	protected function ensureFetched(){
		if (1 == count($this->dbValues) && 'id' == array_keys($this->dbValues)[0]){
			$query = "SELECT * FROM `{$this->table}` WHERE ";
			$query .= 0 >= $this->dbValues['id'] ? 'FALSE' : "`id` = {$this->dbValues['id']} ";
			$query .= 'limit 1;';
			$query = Database::dbr()->query($query, PDO::FETCH_ASSOC)->fetchAll();
			if (0 < count($query)){
				$this->dbValues = $query[0];
			}
		}
	}

	//ArrayAccess Interface
	public function offsetExists($offset): bool{
		$this->ensureFetched();
		$output = array_key_exists($offset, $this->dbValues);
		$output = $output || array_key_exists("{$offset}_id", $this->dbValues);
		$output = $output || UStr::ends_with($offset, '_list', false);
		return $output;
	}

	public function offsetGet($offset): mixed{
		$output = null;
		if ($this->offsetExists($offset)){
			if (array_key_exists($offset, $this->wrkValues)){
				$output = $this->wrkValues[$offset];
			} else {
				if (array_key_exists($offset, $this->dbValues)){
					$output = $this->dbValues[$offset];
				} else {
					if (is_null($this->children)){
						$this->children = array();
					}
					if (array_key_exists($offset, $this->children)){
						$output = $this->children[$offset];
					} else {
						if (0 < $this->offsetGet('id') && UStr::ends_with($offset, '_list', false)){
							$output = UStr::substr($offset, 0, UStr::len($offset)-5);
							$output = "Rows_{$output}";
							$output = new $output(array("{$this->table}_id"=>$this->offsetGet('id')));
						} else {
							$id = null;
							if (array_key_exists("{$offset}_id", $this->wrkValues)){
								$id = $this->wrkValues["{$offset}_id"];
							} else if (array_key_exists("{$offset}_id", $this->dbValues)){
								$id = $this->dbValues["{$offset}_id"];
							}
							if (!is_null($id)){
								$output = "Row_{$offset}";
								$output = new $output($id);
								$this->children[$offset] = $output;
							}
						}
					}
				}
			}   
		}
		return $output;
	}

	public function offsetSet($offset, $value): void{
		$changed = false;
		if ($this->offsetExists($offset)){
			if ($this->offsetGet($offset) != $value){
				$this->wrkValues[$offset] = $value;
				$changed = true;
			}
		}
		if ($changed && UStr::ends_with($offset, '_id', false)){
			$child = Ustr::substr($offset, 0, UStr::len($offset)-3);
			unset($this->children[$child]);
		}
	}

	public function offsetUnset($offset): void{
		if ($this->offsetExists($offset)){
			$this->wrkValues[$offset] = ('id' == $offset) ? 0 : null;
		}
	}
        
	public function __get($key){
		return $this->offsetGet($key);
	}

	public function __set($key, $value){
		$this->offsetSet($offset, $value);
	}

	//Iterator Interface
	public function current(): mixed{
		$key = $this->key();
		if (array_key_exists($key, $this->wrkValues)){
			return $this->wrkValues[$key];
		}
		return $this->dbValues[$key];
	}

	public function key(): mixed{
		if ($this->iteratorIndex >= count($this->dbValues)){
			$this->iteratorIndex = 0;
		}
		return array_keys($this->dbValues)[$this->iteratorIndex];
	}

	public function next(): void{
		$this->iteratorIndex++;
	}

	public function rewind(): void{
		$this->iteratorIndex = 0;
	}

	public function valid(): bool{
		return $this->iteratorIndex < count($this->dbValues);
	}

	//Stringable Interface
	public function __toString(): string{
		$indent = 0;
		if (1 < func_num_args()){
			$arg = func_get_arg(1);
			if (is_numeric($arg)){
				$indent = $arg;
			}
		}
		$this->ensureFetched();
		$output = UStr::jsonify($this, $indent);
		return $output;
	}

	//Countable Interface
	public function count(): int{
		$this->ensureFetched();
		return count($this->dbValues);
	}
}
?>
