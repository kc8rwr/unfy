<?php

/** 
 * @brief Unfy String class.
 * 
 * This is a class containing static methods for manipulating strings. It should mirror PHP's own
 * string functions. The purpose of this class is that when multibyte string methods are available
 * it should default to using them. However when they are not it should gracefully fail back to
 * the standard non-multibyte built in PHP string functions.
 *
 * For the most part methods should take the same arguments and have the same behaviors as their
 * standard function counterparts. There may be some cases where optional arguments are added to
 * enable additional functionality.
 *
 * For ease of use methods will are basically named the same as their original PHP function
 * counterparts. However for increased constency and also for brevity (since the user will be
 * prefixing all calls to these methods with 'UStr::' anyway) any names beginning with the
 * str or str_ prefixes will not include this part.
 */
class UStr{

	/** 
	 * Return character by Unicode code point value.
	 * 
	 * @param in - int code point 
	 * @param encoding - string The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - string
	 */
	public static function chr(int $in, ?string $encoding = null): string {
		if (static::has_mb()){
			return mb_chr($in, $encoding);
		} else {
			return chr($in);
		}
	}

	
	/** 
	 * @brief Split up a string by length by inserting a separating string.
	 *
	 * Can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode() output to match RFC 2045 semantics.
	 * It inserts separator every length characters.
	 * @param string - string The string to be chunked.
	 * @param length - int The chunk length.
	 * @param separator - string The line endig sequence.
	 * @param encoding - string Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - string The chunked output string.
	 */
	public static function chunk_split(string $string, int $length = 76, string $separator = "\r\n", ?string $encoding = null): string {
		if (string::has_mb()){
			return static::implode($separator, static::split($string, $length, $encoding));
		} else {
			return chunk_split($string, $length, $separator);
		}
	}

	/** 
	 * Determine if a string contains a given substring.
	 * 
	 * @param haystack - string The string to search in.
	 * @param needle - string The substring to search for.
	 * @param caseSensitive - bool Should the search be case sensitive?
	 * 
	 * @return bool - Does the haystack string contain the needle string.
	 */
	public static function contains(string $haystack, string $needle, bool $caseSensitive = true):bool {
		if (!$caseSensitive){
			$haystack = Static::toLower($haystack);
			$needle = Static::toLower($needle);
		}
		if (function_exists('str_contains')){
			return str_contains($haystack, $needle);
		} else {
			return false !== UStr::strpos($haystack, $needle);
		}
	}

	/** 
	 * Does the haystack string end with the contents of the needle string?
	 * 
	 * @param haystack string
	 * @param needle string
	 * @param caseSensitive false to ignore case 
	 * 
	 * @return bool
	 */
	static function ends_with(string $haystack, string $needle, bool $caseSensitive = true):bool {
		if (static::has_mb()){
			$len_needle = mb_strlen($needle);
			if (0 == $len_needle){
				return true;
			} else {
				if (mb_strlen($haystack) < $len_needle) {
					return false;
				} else {
					if ($caseSensitive) {
						return mb_substr($haystack, mb_strlen($haystack)-(mb_strlen($needle))) == $needle;
					} else {
						return mb_strtolower(mb_substr($haystack, mb_strlen($haystack)-(mb_strlen($needle)))) == mb_strtolower($needle);
					}
				}
			}
		} else if ($caseSensitive) {
			return str_ends_with($haystack, $needle);
		} else {
			return str_ends_with(str_to_lower($haystack), str_to_lower($needle));
		}
	}

	/** 
	 * Does this PHP install include the mbstring module?
	 * 
	 * @return bool
	 */
	static function has_mb():bool {
		return extension_loaded('mbstring');
	}

	/** 
	 * @brief Join array elements with a string.
	 * 
	 * Join array elements with a string. (Arguments are reversed from built in function so as to put optional arguments last.)
	 * @param array - array The array of strings to implode.
	 * @param separator - string The separator string.
	 * @param encoding - string Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return 
	 */
	public static function implode(array $array, string $separator = '', ?string $encoding = null):string {
		// TESTME - Pretty sure multibyte does not matter for this one
		return implode($string, $array);
	}

	/** 
	 * Recursively convert an object to a json string.
	 * 
	 * @param src - object
	 * @param ident - int Indent each line by this many characters.
	 * @param indentFirstLine - bool Should the first line be indented. 
	 * 
	 * @return 
	 */
	static function jsonify($src, int $ident = 0, bool $indentFirstLine = false):string {
		$escape = function($in){
			$out = $in;
			if (($out instanceof \UnitEnum || $out instanceof \BackedEnum)){
				$out = $out->name;
			}
			$out = null == $out ? '' : addcslashes($out, '\\\"');
			return $out;
		};
		$stIdent = str_repeat("\t", $ident);
		$output = '';
		if (is_array($src) || $src instanceof ArrayAccess || $src instanceof Traversable){
			foreach($src as $key=> $value){
				if (!is_numeric($value)){
					if (is_array($value) || $value instanceof ArrayAccess || $value instanceof Traversable){
						$value = UStr::jsonify($value, $ident+1, false);
					} else {
						if (is_object($value) && method_exists($value, '__toString')){
							$value = $value->__toString($ident+1);
						} else {
							$value = '"'.$escape($value).'"';
						}
					}
				}
				if (!empty($output)){
					$output .= ",\n";
				}
				$output .= "{$stIdent}\t\"{$key}\": {$value}";
			}
			$output = ($indentFirstLine ? $indent : '') . "{\n{$output}\n{$stIdent}}";
		} else {
			$output = empty($src) ? '' : "'" . $escape($src) . "'";
			$output = $stIdent . '{' . $output . '}';
		}
		return $output;
	}

	/** 
	 * How many characters are in the passed string?
	 * 
	 * @param in 
	 * 
	 * @return integer
	 */
	static function len(string $in):int {
		if (static::has_mb()){
			return mb_strlen($in);
		} else {
			return str_len($in);
		}
	}

	/** 
	 *  @brief Strip whitespace from the beginning of a string.
	 * 
	 * Strip whitespace (or other characters) from the beginning of a string.
	 * Adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param haystack 
	 * @param needles - string list of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * @return - string the trimmed result. 
	 */
	public static function ltrim(string $haystack, string $needles=" \n\r\t\v\x00"):string {
		if (empty($haystack)) return $haystack;
		if (static::has_mb()){
			if (function_exists('mb_ltrim')){ //new as of php 8.4
				return mb_trim($haystack, $needles);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/(^[$needles]+)/u", '', $haystack);
			}
		} else {
			return ltrim($haystack, $needles);
		}
	}

	/** 
	 * Find the position of the first occurrence of a substring in a string
	 * 
	 * @param haystack - string The string to be searched
	 * @param needle - string The string to search for
	 * @param offset - int If specified, search will start this number of characters counted from the beginning of the string. If the offset is negative, the search will start this number of characters counted from the end of the string.
	 * @param encoding - string Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - int position number or false if not found
	 */
	public static function pos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = false;
		if (static::has_mb()){
			$output =  mb_strpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strpos($haystack, $needle, $offset, $encoding);
		}
		return $output;
	}

	/** 
	 * Replace all occurrences of the search string with the replacement string
	 * 
	 * @param search - string the string segment that is to be replaced
	 * @param replace - string the segment that is to replace the search segment
	 * @param subject - the input string
	 * @param &count - int If passed, this will be set to the number of replacements performed.
	 * 
	 * @return - string output
	 */
	static function replace($search, $replace, $subject, &$count = null){
		//according to comment at https://www.php.net/manual/en/ref.mbstring.php a multibyte implementation is not necessary
		return str_replace($search, $replace, $subject, $count);
	}

	/** 
	 * Find the position of the last occurrence of a substring in a string
	 * 
	 * @param haystack - string The string to be searched
	 * @param needle - string The string to search for
	 * @param offset - int
	 * - If zero or positive, the search is performed left to right skipping the first offset bytes of the haystack.
	 * - If negative, the search starts offset bytes from the right instead of from the beginning of haystack. The search is performed right to left, searching for the first occurrence of needle from the selected byte.
	 * @param encoding - string Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - int position number or false if not found
	 */
	public static function rpos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null):int|false {
		$output = null;
		if (static::has_mb()){
			$output =  mb_strrpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strrpos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * @preview Strip whitespace from the end of a string.
	 * 
	 * Strip whitespace (or other characters) from the end of a string.
	 * Adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param haystack 
	 * @param needles - string list of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * @return - string the trimmed result. 
	 */
	public static function rtrim(string $haystack, string $needles=" \n\r\t\v\x00"):string {
		if (empty($haystack)) return $haystack;
		if (static::has_mb()){
			if (function_exists('mb_trim')){ //new as of php 8.4
				return mb_trim($haystack, $needles);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/([$needles]+$)/u", '', $haystack);
			}
		} else {
			return rtrim($haystack, $needles);
		}
	}

	/** 
	 * Split a string into an array by character count.
	 * 
	 * @param string - string to be split.
	 * @param length - int charachter length of split sections.
	 * @param encoding - string Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - array of strings
	 */
	public static function split(string $string, int $length = 1, ?string $encoding = null): array {
		if (static::has_mb()){
			return mb_str_spit($string, $length, $encoding);
		} else {
			return str_split($string, $length);
		}
	}
	
	/** 
	 * Does the haystack string begin with the contents of the needle string?
	 * 
	 * @param haystack string
	 * @param needle string
	 * @param caseSensitive false to ignore case 
	 * 
	 * @return bool
	 */
	static function starts_with(string $haystack, string $needle, bool $caseSensitive = true):bool {
		if (empty($haystack)) return false;
		if (static::has_mb()){
			$len_needle = mb_strlen($needle);
			if (0 == $len_needle){
				return true;
			} else {
				if (mb_strlen($haystack) < $len_needle) {
					return false;
				} else {
					if ($caseSensitive) {
						return mb_substr($haystack, 0, $len_needle) == $needle;
					} else {
						return mb_strtolower(mb_substr($haystack, 0, $len_needle)) == mb_strtolower($needle);
					}
				}
			}
		} else if ($caseSensitive) {
			return str_starts_with($haystack, $needle);
		} else {
			return str_starts_with(str_to_lower($haystack), str_to_lower($needle));
		}
	}

	/** 
	 * Return part of a string
	 * 
	 * @param in - string input
	 * @param offset - int
	 * - If offset is non-negative, the returned string will start at the offset'th position in string, counting from zero. For instance, in the string 'abcdef', the character at position 0 is 'a', the character at position 2 is 'c', and so forth.
	 * - If offset is negative, the returned string will start at the offset'th character from the end of string.
	 * - If string is less than offset characters long, an empty string will be returned.
	 * @param length - int
	 * - If length is given and is positive, the string returned will contain at most length characters beginning from offset (depending on the length of string).
	 * - If length is given and is negative, then that many characters will be omitted from the end of string (after the start position has been calculated when a offset is negative). If offset denotes the position of this truncation or beyond, an empty string will be returned.
	 * - If length is given and is 0, an empty string will be returned.
	 * - If length is omitted or null, the substring starting from offset until the end of the string will be returned
	 * 
	 * @return - string output
	 */
	static function substr(string $in, int $offset, ?int $length = null):string {
		if (static::has_mb()){
			return mb_substr($in, $offset, $length);
		} else {
			return substr($in, $offset, $length);
		}
	}

	/** 
	 * Generate a lowercase version of the string
	 * 
	 * @param in - string input
	 * 
	 * @return - string lowercased output
	 */
	static function toLower(string $in):string {
		if (static::has_mb()){
			return mb_strToLower($in);
		} else {
			return strToLower($in);
		}
	}

	/** 
	 * Generate an uppercase version of the string
	 * 
	 * @param in - string input
	 * 
	 * @return - string uppercased output
	 */
	static function toUpper(string $in):string {
		if (static::has_mb()){
			return mb_strToUpper($in);
		} else {
			return strTUpper($in);
		}
	}


	/** 
	 *  @brief Strip whitespace from the beginning and end of a string.
	 *
	 * Strip whitespace (or other characters) from the beginning and end of a string.
	 * Solution adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param haystack 
	 * @param needles - string list of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * @return - string the trimmed result. 
	 */
	public static function trim(string $haystack, string $needles=" \n\r\t\v\x00"):string {
		if (empty($haystack)) return $haystack;
		if (static::has_mb()){
			if (function_exists('mb_trim')){ //new as of php 8.4
				return mb_trim($haystack, $needles);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/(^[$needles]+)|([$needles]+$)/u", '', $haystack);
			}
		} else {
			return trim($haystack, $needles);
		}
	}
}

?>
