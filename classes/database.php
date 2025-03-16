<?php

class Database{

	private static $dbconn_w = null;
	private static $dbconn_r = null;
	private static $type = null;
	
	public static function init(){
		$output = array('success'=> false, 'message'=>'');
        
		$params = Unfy::getFig('database');
		if (!empty(@$params['type'])){
			static::$type = UStr::toLower($params['type']);
		}
		
		switch(static::$type){
			case 'mysql':
				if (empty($params['host'])
					 || empty($params['name'])
					 || empty($params['username_r'])
					 || empty($params['password_r'])
					 || empty($params['username_w'])
					 || empty($params['password_w'])
				){
					$output['success'] = false;
					$output['message'] = 'bad db config';
				} else {
					$dsn = "mysql:host={$params['host']};dbname={$params['name']};charset=UTF8";
					try{
						static::$dbconn_r = new PDO($dsn, $params['username_r'], $params['password_r']);
						static::$dbconn_w = new PDO($dsn, $params['username_w'], $params['password_w']);
					} catch (PDOException $e){
						$output['success'] = false;
						$output['message'] = 'bad db config';
					}
				}
				break;
			case 'sqlite':
				$base_path = Unfy::getFig('base_path');
				try{
					static::$dbconn_r = new PDO("sqlite:{$params['path']}");
				}
				catch (PDOException $e){
					hdd($e);
					$output['success'] = false;
					$output['message'] = 'bad db config bob';
				}
				static::$dbconn_w = static::$dbconn_r;
				break;
			default:
				$output['success'] = false;
				$output['message'] = 'bad db config';
				break;
		}
    
		return $output;
	}

	public static function getType(){
		if (is_null(static::$type)){
			static::init();
		}
		return static::$type;
	}
	
	public static function escape($in){
		$escaped = self::dbr()->quote($in);
		return ustr::substr($escaped, 1, ustr::len($escaped)-2);
	}

	public static function dbr(){
		if (is_null(static::$dbconn_r)){
			static::init();
		}
		return static::$dbconn_r;
	}
    
	public static function dbw(){
		if (is_null(static::$dbconn_w)){
			static::init();
		}
		return static::$dbconn_w;
	}

	//Schema check

	public static function tableExists($table){
		$table = UStr::toLower($table);
		if (true || null == Unfy::getCache('base_rows_tables')){
			$query = null;
			if ('mysql' == static::getType()){
				$query = 'SHOW TABLES;';
			}
			else if ('sqlite' == static::getType()){
				$query = "SELECT `name` FROM `sqlite_schema` WHERE `type` ='table' AND `name` NOT LIKE 'sqlite_%';";
			}
			if (null != $query){
				$query = static::dbr()->query($query, PDO::FETCH_COLUMN, 0)->fetchAll();
				if(is_array($query)){
					Unfy::setCache('base_rows_tables', $query);
				}
			}
		}
		$tables = Unfy::getCache('base_rows_tables');
		return (is_array($tables) && in_array($table, $tables));
	}
	
}
?>
