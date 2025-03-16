<?php

class Column implements Stringable {
	private $attributes = array();
	
	public function __construct($name, $type, $dbtype, $length=null, $min=null, $max=null, $default=null){
		$this->attributes['name'] = $name;
		$this->attributes['type'] = $type;
		$this->attributes['length'] = $length;
		$this->attributes['min'] = $min;
		$this->attributes['max'] = $max;
		$this->attributes['default'] = $default;
		$this->attributes['dbtype'] = $dbtype;
	}

	public function __get($_key){
		switch($_key){
			case 'name':
			case 'type':
			case 'length':
			case 'min':
			case 'max':
			case 'default':
			case 'dbtype':
				return $this->attributes[$_key];
				break;
		}
	}

	// stringable interface
	public function __toString(): string{
		$indent = 0;
		if (0 < func_num_args()){
			$arg = func_get_arg(0);
			if (is_numeric($arg)){
				$indent = $arg;
			}
		}
		$output = UStr::jsonify($this->attributes, $indent);
		return $output;
	}

}

enum ColumnType{
	case Int;
	case Float;
	case String;
	case Bool;
	case DateTime;
}


abstract class Table{
	private static $name = null;
	private static $columns = null;
	
	public static function getName(){
		if (empty(static::$name)){
			if (6 < uStr::len(static::class) && uStr::starts_with(static::class, "table_", false)){
				static::$name = uStr::toLower(Database::escape(uStr::substr(static::class, 6)));
			}
		}
		return static::$name;
	}

	public static function getColumns(){
		if (null == static::$columns)
		{
			$tableName = static::getName();
			$cached = Unfy::getCache("database_table_{$tableName}");
			if (is_array($cached)){
				static::$columns = $cached;
			} else {
				$columns = array();
				switch (Database::getType()){
					case 'mysql':
						/* field - column name
						   type - datatype w/ extra info
							null - yes / no
							key - pri / empty / ?
							default - default val
							extra - auto_increment / ? */
						$query = "SHOW COLUMNS FROM `{$tableName}`;";
						$query = Database::dbr()->query($query, PDO::FETCH_ASSOC)->fetchAll();
						if (is_array($query)){
							foreach ($query as $column){
								$type = null;
								$arType = explode(' ', $column['Type']);
								$isUnsigned = in_array('unsigned', $arType);
								$length = null;
								$min = null;
								$max = null;

								if (UStr::ends_with($arType[0], ')')){
									$begI = uStr::strpos($arType[0], '(');
									if (false !== $begI && uStr::len($arType[0])-1 > $begI){
										$length = uStr::substr($arType[0], $begI+1, uStr::len($arType[0])-2-$begI);
										$arType[0] = uStr::substr($arType[0], 0, $begI);
									}
								}

								switch (uStr::toLower($arType[0])){
									case 'tinyint' :
										$type = ColumnType::Int;
										if ($isUnsigned){
											$min = 0;
											$max = 255;
										} else {
											$min = -128;
											$max = 127;
										}
										break;
									case 'smallint' :
										$type = ColumnType::Int;
										if ($isUnsigned){
											$min = 0;
											$max = 65535;
										} else {
											$min = -32768;
											$max = 32767;
										}
										break;
									case 'mediumint' :
										$type = ColumnType::Int;
										if ($isUnsigned){
											$min = 0;
											$max = 16777215;
										} else {
											$min = -8388608;
											$max = 8388607;
										}
										break;
									case 'int' :
										$type = ColumnType::Int;
										if ($isUnsigned){
											$min = 0;
											$max = 4294967295;
										} else {
											$min = -2147483648;
											$max = 2147483647;
										}
										break;
									case 'bigint' :
										$type = ColumnType::Int;
										if ($isUnsigned){
											$min = 0;
											$max = pow(2, 64)-1;
										} else {
											$min = -pow(2, 63);
											$max = pow(2, 63)-1;
										}
										break;
									case 'decimal':
										$arLength = explode(',', $length);
										$arLength[0] -= $arLength[1];
										$type = 0 == $arLength[1] ? ColumnType::Int : ColumnType::Float;
										$stMax = str_repeat('9', $arLength[0]);
										$max = (int)$stMax;
										$min = $isUnsigned ? 0 : -1 * $max;
										break;
									case 'float': //unsinged values in float/double are deprecated in MySQL8.0+
										$type = ColumnType::Float; //do not know about Maria or others
										$max = 3.402823466E+38; //unsigned only ever disallowed negatives anyway, it did not increase positive range
										$min = $isUnsigned ? 0 : (-1 * $max);
										break;
									case 'double':
										$type = ColumnType::Float;
										$max = 1.7976931348623157E+308;
										$min = $isUnsigned ? 0 : (-1 * $max);
										break;
									case 'char':
									case 'varchar':
										$type = ColumnType::String;
										break;
									case 'tinytext':
										$length = 255;
										$type = ColumnType::String;
										break;										
									case 'text':
										$length = 65535;
										$type = ColumnType::String;
										break;										
									case 'mediumtext':
										$length = 16777215;
										$type = ColumnType::String;
										break;										
									case 'longtext':
										$length = 4294967295;
										$type = ColumnType::String;
										break;
									case 'date':
									case 'datetime':
									case 'timestamp':
									case 'time':
										$type = ColumnType::DateTime;
										break;
									case 'year':
										$length = empty($length) ? 4 : $length;
										$stMax = str_repeat('9', $length);
										$max = (int)$stMax;
										$min = 0;
										break;
								}

								if (null != $type){
									$colObj = new Column($column['Field'], $type, $arType[0], $length, $min, $max, $column['Default']);
									$columns[$colObj->name] = $colObj;
								}
							}
						}
						break;
					case 'sqlite':
						/* cid, name, type, notnull 0/1, dflt_value, pk (primary key) 1,0 */
						$query = "pragma table_info(`{$tableName}`);";
						$query = Database::dbr()->query($query, PDO::FETCH_ASSOC)->fetchAll();
						if (is_array($query)){
							foreach ($query as $column){
								$type = null;
								$default = null;
								$isUnsigned = false;
								$min = null;
								$max = null;
								$length = null;
								switch ($column['type']){
									case 'INTEGER':
										$type = ColumnType::Int;
										if ($isUnsigned){
											$min = 0;
											$max = 18446744073709551615;
										} else {
											$min = -9223372036854775808;
											$max = 9223372036854775807;
										}
										break;
									case 'REAL':
									case 'NUMERIC':
										$type = ColumnType::Float;
										$max = 1.7976931348623157E+308;
										$min = $isUnsigned ? 0 : (-1 * $max);
										if (is_numeric($column['dflt_value'])){
											$default = $column['dflt_value'];
										}
										break;
									case 'TEXT':
										$type = ColumnType::String;
										$length = 2E+31 - 1;
										if (UStr::starts_with($column['dflt_value'], "'", false) && UStr::ends_with($column['dflt_value'], "'", false)){
											$default = UStr::trim($column['dflt_value'], "'");
										}
										break;
									case 'BOOLEAN':
										$type = ColumnType::Bool;
										break;
									case 'DATETIME':
										$type = ColumnType::DateTime;
										break;
								}
								if (null != $type){
									$colObj = new Column($column['name'], $type, $column['type'], $length, $min, $max, $default);
									$columns[$colObj->name] = $colObj;
								}
							}
						}
						break;
					default:
						break;
				}				
				Unfy::setCache("database_table_{$tableName}", $columns);
				static::$columns = $columns;
			}
		}
		return static::$columns;
	}
	
	public static function hasColumn($colName){
		return array_key_exists($colName, static::getColumns());
	}

	public static function toString($indent = 0){
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
		$output .= "{$indent}\t\"name\": \"" . $escape(static::getName()) . "\",\n";
		$output .= "{$indent}\t\"columns\": " . UStr::jsonify(static::getColumns(), 1) . "\n";
		$output .= "{$indent}}\n";
		return $output;

	}
		
}


?>
