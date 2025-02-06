<?php

class Database{

	private static $dbconn_w = null;
	private static $dbconn_r = null;

	public static function init(){
		$output = array('success'=> false, 'message'=>'');
        
		$params = Unfy::getFig('database');
		switch(@$params['type']){
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
					static::$dbconn_r = new PDO("sqlite:{$base_path}{$params['path']}");
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
    
}
?>
