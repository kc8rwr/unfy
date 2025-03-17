<?php

/** 
 * @brief Database class.
 *
 * Represents the database with static methods. Instantiates the read and write db conections. Also queries the
 * database schema.
 * 
 * @return 
 */class Database{

	private static $dbconn_w = null;
	private static $dbconn_r = null;
	private static $type = null;

	 /** 
	  * @brief Database class init().
	  * 
	  * Initializes the Database class by reading the connection info from preferences and instantiating the
	  * read and write connections.
	  */
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

	 /** 
	  * @brief MySQL or SQLite.
	  *
	  * Returns the type of the database MySQL or SQLite. 
	  * @return string - the type of the database MySQL/SQLite
	  */
	 public static function getType(){
		if (is_null(static::$type)){
			static::init();
		}
		return static::$type;
	}

	 /** 
	  * @brief Escape a string for the database.
	  *
	  * Escapes a string for use in the correct database type. Does not add the quotes. This way it may be used
	  * for escaping strings that will be used as column or table names as well as data.
	  * @param in string - the string to escape
	  * 
	  * @return string - the string with escape characters added
	  */ 
	public static function escape($in){
		$escaped = self::dbr()->quote($in);
		return ustr::substr($escaped, 1, ustr::len($escaped)-2);
	}

	 /** 
	  * @brief The read-only database connection.
	  * 
	  * If the database type supports read-only connections gets a read-only connection. Otherwise (such as
	  * with SQLite) just gets a read/write connection. Use this for database reads instead of the r/w
	  * conection for an added layer of security.
	  * @return PDO database connection
	  */
	public static function dbr(){
		if (is_null(static::$dbconn_r)){
			static::init();
		}
		return static::$dbconn_r;
	}

	 /** 
	  * Returns the read/write database conection.
	  *
	  * @return PDO database connection in r/w mode 
	  */
	public static function dbw(){
		if (is_null(static::$dbconn_w)){
			static::init();
		}
		return static::$dbconn_w;
	}

	//Schema check

	 /** 
	  * 
	  * 
	  * @param table 
	  * 
	  * @return 
	  */ 
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
