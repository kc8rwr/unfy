<?php

UStr::$has_mb = extension_loaded('mbstring');

/** 
 * @brief Unfy String class.
 * 
 * This is a class containing static methods for manipulating strings. It should mirror PHP's own
 * string functions. The purpose of this class is that when the mbstrings extension is available
 * it should default to using it. However when it is not it should gracefully fail back to
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
 *
 * Wrappers for some string functions which already worked with multibyte strings are included just for ease of use.
 */
class UStr{

	public static $has_mb = null;
	
	/// A string containing known 'whitespace' characters, used by trim, ltrim, rtrim methods
	const SPACE_NEEDLE = " \n\r\t\v\f\x00\u{00A0}\u{1680}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200A}\u{2028}\u{2029}\u{202F}\u{205F}\u{3000}\u{0085}\u{180E}";
	const CASE_LOWER = MB_CASE_LOWER;
	const CASE_UPPER = MB_CASE_UPPER;
	const CASE_TITLE = MB_CASE_TITLE;
	const CASE_FOLD = MB_CASE_FOLD;
	const CASE_LOWER_SIMPLE = MB_CASE_LOWER_SIMPLE;
	const CASE_UPPER_SIMPLE = MB_CASE_UPPER_SIMPLE;
	const CASE_TITLE_SIMPLE = MB_CASE_TITLE_SIMPLE;
	const CASE_FOLD_SIMPLE = MB_CASE_FOLD_SIMPLE;
	
	/** 
	 * Quote string with slashes in a C style.
	 *
	 * @param string The string to be escaped.
	 * @param characters A list of characters to be escaped. Can include ranges using '..'.
	 * @param encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @warning Because multibyte characters in some character sets may contain the backslash byte addcslashes() is not a safe way to prevent sql injection. Use the correct method which is made for this purpose such as PDO::quote or mysqli::real_escape_string.
	 *
	 * @return Returns the escaped string.
	 */
	public static function addcslashes(string $string, string $characters, ?string $encoding=null):string {
		if (static::$has_mb){
			$characters = static::toCharList($characters, $encoding);
			$output = $string;
			if (in_array('\\', $characters)){ //escape backslashes first if that is in the list so that added backslashes do not get escaped
				$output = static::replace('\\', '\\\\', $output);
			}
			foreach ($characters AS $char){
				if ('\\' != $char){
					$output = static::replace($char, '\\'.$char, $output);
				}
			}
			return $output;
		} else {
			return addcslashes($string, $characters);
		}
	}

	/** 
	 * @brief Quote string with slashes.
	 * Returns a string with backslashes added before characters that need to be escaped. These characters are:
	 * - single quote (')
	 * - double quote (")
	 * - backslash (\)
	 * - NULL (the NULL byte)
	 *
	 * @warning Not secure for escaping sql strings. Use the correct PDO escape function for your database type, mysql_real_escape_string or other apropriate method for your database type.
	 * @param string The input string.
	 * @param $encoding Not used for this method, only there for consistency.
	 * 
	 * @return The escaped string.
	 */
	public static function addslashes(string $string, ?string $encoding=null):string {
		return addslashes($string);
	}
	
	/** 
	 * Binary safe case-insensitive string comparison.
	 * 
	 * @param $string1 The first string.
	 * @param $string2 The second string.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function casecmp(string $string1, string $string2, ?string $encoding=null):int {
		if (static::$has_mb){ 
			//from https://www.php.net/manual/en/function.strcasecmp.php comment by chris at cmbuckley dot co dot uk
			$encoding = null == $encoding ? mb_internal_encoding() : $encoding;
			return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
		} else {
			return strcasecmp($string1, $string2);
		}
	}

	/** 
	 *  Check if strings are valid for the specified encoding.
	 * 
	 * @param value The byte stream or array to check.
	 * @param encoding The expected encoding.
	 * 
	 * @return Returns true on success or false on failure.
	 */
	public static function check_encoding(array|string|null $value = null, ?string $encoding = null): bool {
		if (static::$has_mb){
			return mb_check_encoding($value, $encoding);
		} else {
			return true;
		}
	}
	
	/** 
	 * Return character by Unicode code point value.
	 * 
	 * @param $in The code point.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - string The character.
	 */
	public static function chr(int $in, ?string $encoding=null): string {
		if (static::$has_mb){
			return mb_chr($in, $encoding);
		} else {
			return chr($in);
		}
	}

	/** 
	 * Binary safe string comparison.
	 * 
	 * @param $string1 The first string.
	 * @param $string2 The second string.
	 * @param $encoding Not used for this method, only there for consistency.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function cmp(string $string1, string $string2, ?string $encoding=null){
		return (strcmp($string1, $string2));
	}
	
	/** 
	 * Binary safe string comparison using current locale.
	 * 
	 * @param $string1 The first string.
	 * @param $string2 The second string.
	 * @param $encoding Not used for this method, only there for consistency.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function coll(string $string1, string $string2, ?string $encoding=null){
		return (strcoll($string1, $string2));
	}
	
	/** 
	 * @brief Split up a string by length by inserting a separating string.
	 *
	 * Can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode() output to match RFC 2045 semantics.
	 * It inserts separator every length characters.
	 * @param $string The string to be chunked.
	 * @param $length The chunk length.
	 * @param $separator The line endig sequence.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return string - The chunked output string.
	 */
	public static function chunk_split(string $string, int $length=76, string $separator="\r\n", ?string $encoding=null): string {
		if (static::$has_mb){
			return static::implode($separator, static::split($string, $length, $encoding));
		} else {
			return chunk_split($string, $length, $separator);
		}
	}

	/** 
	 * Determine if a string contains a given substring.
	 * 
	 * @param $haystack The string to search in.
	 * @param $needle The substring to search for.
	 * @param $caseSensitive Should the search be case sensitive?
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return bool - Does the haystack string contain the needle string.
	 */
	public static function contains(string $haystack, string $needle, bool $caseSensitive=true, ?string $encoding=null):bool {
		if (!$caseSensitive){
			return static::contains(Static::toLower($haystack), Static::toLower($needle), $encoding);
		}
		if (function_exists('str_contains')){
			return str_contains($haystack, $needle);
		} else {
			return false !== UStr::strpos($haystack, $needle, $encoding);
		}
	}

	/** 
	 * @brief Perform case folding on a string.
	 *
	 * Performs case folding on a string, converted in the way specified by mode.
	 * @param $string The string being converted.
	 * @param $mode The mode of the conversion. It can be one of:
	 * - UStr::CASE_UPPER (convert to all-uppercase)
	 * - UStr::CASE_LOWER (convert to all-lowercase)
	 * - UStr::CASE_TITLE (uppercase first char of each word)
	 * - UStr::CASE_FOLD
	 * - UStr::CASE_UPPER_SIMPLE
	 * - UStr::CASE_LOWER_SIMPLE
	 * - UStr::CASE_TITLE_SIMPLE
	 * - UStr::MB_CASE_FOLD_SIMPLE
	 * @note With MB implementation Simple always tramslates chars 1-1 where as with some specific characters in some languages mappings might not be 1-1. ex) upper of ß = SS vs ẞ. CASE_FOLD is basically
	 * uppercasing except for exceptions in some languages such that the single-bit implementation is just uppercasing.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return 
	 */
	public static function convert_case(string $string, int $mode, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_convert_case($string, $mode, $encoding);
		} else {
			switch ($mode){
				case CASE_LOWER:
				case CASE_LOWER_SIMPLE:
					return strtolower($string);
					break;
				case CASE_UPPER:
				case CASE_FOLD:
				case CASE_UPPER_SIMPLE:
				case CASE_FOLD_SIMPLE:
					return strtoupper($string);
					break;
				case CASE_TITLE:
				case CASE_TITLE_SIMPLE:
					return ucwords($string);
					break;
				default:
					return false;
					break;
			}
		}
	}

	/** 
	 * Find length of initial segment not matching characters in mask.
	 * 
	 * @param $string The string to examine.
	 * @param $characters The string containing every disallowed character.
	 * @param $offset The position in string to start searching.
	 * - If offset is given and is non-negative, then strcspn() will begin examining string at the offset'th position. For instance, in the string 'abcdef', the character at position 0 is 'a', the character at position 2 is 'c', and so forth.
	 * - If offset is given and is negative, then strcspn() will begin examining string at the offset'th position from the end of string.
	 * @param $length The length of the segment from string to examine.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 * - If length is given and is non-negative, then string will be examined for length characters after the starting position.
	 * - If length is given and is negative, then string will be examined from the starting position up to length characters from the end of string. 
	 *
	 * @return Returns the length of the initial segment of string which consists entirely of characters not in characters. 
	 */
	public static function cspn(string $string, string $characters, int $offset=0, ?int $length=null, ?string $encoding=null): int {
		if (static::$has_mb){
			if (0 < $offset || null != $length){
				return static::cspn(static::substr($string, $offset, $length), $characters, 0, null, $encoding);
			}
			$output = static::len($string);
			$arChars = static::split($characters);
			$arChars = array_unique($arChars);
			foreach($arChars as $char){
				$pos = min(static::pos($string, $char, 0, $encoding));
				if (false !== $pos){
					if (0 == $pos) return 0;
					$output = $pos;
					$string = static::substr($string, 0, $pos+1);
				}
			}
			return $output;
		} else {
			return strcspn($string, $characters, $offset, $length);
		}
	}
	
	/** 
	 * Does the haystack string end with the contents of the needle string?
	 * 
	 * @param $haystack The string to check the end of.
	 * @param $needle The ending to look for.
	 * @param $caseSensitive False to ignore case.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 *
	 * @return bool
	 */
	public static function ends_with(string $haystack, string $needle, bool $caseSensitive=true, ?string $encoding=null):bool {
		if (!$caseSensitive){
			return static::ends_with(static::toLower($haystack, $encoding), static::toLower($needle, $encoding), true);
		}
		if (function_exists('str_ends_with')){
			return str_ends_with($haystack, $needle);
		} else {
			$len_needle = static::len($needle, $encoding);
			if (0 == $len_needle){
				return true;
			} else {
				if (static::len($haystack, $encoding) < $len_needle) {
					return false;
				} else {
					return static::substr($haystack, -$len_needle, encoding: $encoding) == $needle;
				}	
			}
		}
	}

	/** 
	 * Split a string by a string.
	 * 
	 * @param $separator The boundary string.
	 * @param $string The input string.
	 * @param $limit Limit the results.
	 * - If limit is set and positive, the returned array will contain a maximum of limit elements with the last element containing the rest of string.
	 * - If the limit parameter is negative, all components except the last -limit are returned.
	 * - If the limit parameter is zero, then this is treated as 1.
	 * @param $encoding Not currently used, just there for consistency.
	 *
	 * @return 
	 */
	public static function explode(string $separator, string $string, int $limit=PHP_INT_MAX, ?string $encoding=null):array {
		return explode($separator, $string, $limit);
	}

	/** 
	 * @brief Convert HTML entities to their corresponding characters.
	 *
	 * More precisely, this function decodes all the entities (including all numeric entities) that a) are necessarily valid for the chosen document type — i.e., for XML,
	 * this function does not decode named entities that might be defined in some DTD — and b) whose character or characters are in the coded character set associated with
	 * the chosen encoding and are permitted in the chosen document type. All other entities are left as is.
	 * @param $string The input string.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes and which document type to use.
	 * The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 *
	 * @return 
	 */
	public static function html_entity_decode(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, ?string $encoding=null): string {
		return html_entity_decode($string, $flags, $encoding);
	}

	/** 
	 * @brief Convert all applicable characters to HTML entities.
	 *
	 * This function is identical to htmlspecialchars() in all ways, except with htmlentities(), all characters which have HTML character entity equivalents are translated
	 * into these entities. The get_html_translation_table() function can be used to return the translation table used dependent upon the provided flags constants.
	 * @note If you want to decode instead (the reverse) you can use Ustr::html_entity_decode().
	 * @param $string The input string.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes, invalid code unit sequences and the used document type.  The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401. 
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 * @param $double_encode When double_encode is turned off PHP will not encode existing html entities. The default is to convert everything.
	 *
	 * @return 
	 */
	public static function htmlentities(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, ?string $encoding=null, bool $double_encode=true):string {
		 return htmlentities($string, $flags, $encoding, $double_encode);
	 }

	/** 
	 * @brief Convert special characters to HTML entities.
	 *
	 * Certain characters have special significance in HTML, and should be represented by HTML entities if they are to preserve their meanings.
	 * This function returns a string with these conversions made
	 * @note If you require all input substrings that have associated named entities to be translated, use htmlentities() instead.
	 * @param $string The string to be converted.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes, invalid code unit sequences and the used document type. The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
	 * @param $double_encode When double_encode is turned off PHP will not encode existing html entities, the default is to convert everything.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 *
	 * @return The converted string
	 */
	public static function htmlspecialchars(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, bool $double_encode=true, ?string $encoding=null):string {
		return htmlspecialchars($string, $flags, $encoding, $double_encode);
	}

	/** 
	 * Convert special HTML entities back to characters
	 *
	 * @note This function is the opposite of htmlspecialchars().
	 * @param $string The string to decode.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes and which document type to use.
	 * The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
	 * @param $encoding Not currently used, present for consistency.
	 *
	 * @return The decoded string.
	 */
	public static function htmlspecialchars_decode(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, ?string $encoding=null):string {
		return htmlspecialchars_decode($string, $flags);
	}

	/** 
	 * Detect HTTP input character encoding
	 * 
	 * @param type Input string specifies the input type. "G" for GET, "P" for POST, "C" for COOKIE, "S" for string, "L" for list, and "I" for the whole list (will return array). If type is omitted, it returns the last input type processed.
	 * 
	 * @return The character encoding name, as per the type, or an array of character encoding names, if type is "I". If mb_http_input() does not process specified HTTP input, it returns false.
	 */
	public static function http_input(?string $type=null):array|string|false {
		if (static::$has_mb){
			return mb_http_input($type);
		} else {
			$type = null == $type ? static::$http_input_last_type : $type;
			static::$http_input_last_type = $type;
			switch ($type){
				case 'I':
				case 'i':
					return array('ISO-8859-1');
					break;
				case 'L':
				case 'l':
					return 'ISO-885901';
					break;
				case 'C':
				case 'c':
				case 'G':
				case 'g':
				case 'P':
				case 'p':
				case 'S':
				case 's':
					return false;
					break;
				default:
					throw new ValueError('Uncaught ValueError: UStr::http_input(): Argument #1 ($type) must be one of "G", "P", "C", "S", "I"');
					break;
			}				
		}
	}

	/** 
	 * Stores the last type used by http_input.
	 */
	private static $http_input_last_type = "I";
	
	/** 
	 * @brief Join array elements with a string.
	 * 
	 * Join array Elements with a string.
	 * @param $separator The separator string. Default is ','
	 * @param $array The array of strings to implode.
	 * @param $encoding Not currently used, present for consistency.
	 *
	 * @return 
	 */
	public static function implode(string|array $separator, array $array=null, ?string $encoding=null):string {
		if (null == $array && is_array($separator)){
			$array = $separator;
			$separator = ',';
		}
		return implode($separator, $array);
	}

	/** 
	 * Find the position of the first occurrence of a case-insensitive substring in a string
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - If specified, search will start this number of characters counted from the beginning of the string. If the offset is negative, the search will start this number of characters counted from the end of the string.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return - Position number or false if not found as an int.
	 */
	public static function ipos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = false;
		if (static::$has_mb){
			$output =  mb_stripos($haystack, $needle, $offset, $encoding);
		} else {
			$output = stripos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * Replace all occurrences of the case insensitive search string with the replacement string
	 * 
	 * @param $search - The string segment that is to be replaced.
	 * @param $replace - The segment that is to replace the search segment.
	 * @param $subject - The input string.
	 * @param $&count - If passed, this will be set to the number of replacements performed.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - string
	 */
	public static function ireplace(array|string $search, array|string $replace, string|array $subject, int &$count=null, ?string $encoding=null){
		if (false && static::$has_mb){
			$count = 0;
			$search = is_array($search) ? $search : array($search);
			$replace = is_array($replace) ? $replace : array_fill(0, count($search), $replace);
			while (count($replace) < count($search)){
				$replace[] = '';
			}
			for ($i = 0; $i < count($search); $i++){
				$st_search = $search[$i];
				$st_replace = $replace[$i];
				$len_replace = len($st_replace);
				$pos = 0;
				while (false !== $pos = Ustr::ipos($subject, $st_search, $pos, $encoding)){
					$new = '';
					if (0 < $pos){
						$new .= substr($subject, 0, $pos);
					}
					$new .= $st_replace;
					$new .= substr($subject, $pos + $len_replace);
					$subject = $new;
					$pos .= $len_replace; //prevents infinite loop with different case of same substring 
				}
			}
		} else {
			return str_ireplace($search, $replace, $subject, $count);
		}
	}

	/** 
	 * Recursively convert an object to a json string.
	 * 
	 * @param $src - The source object.
	 * @param $indent - Indent each line by this many characters.
	 * @param $indentFirstLine Should the first line be indented (if indent > 0)
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.	 
	 *
	 * @return 
	 */
	public static function jsonify($src, int $indent=0, bool $indentFirstLine=false, ?string $encoding=null):string {
		$escape = function($in){
			$out = $in;
			if (($out instanceof \UnitEnum || $out instanceof \BackedEnum)){
				$out = $out->name;
			}
			$out = null == $out ? '' : addcslashes($out, '\\\"');
			return $out;
		};
		$stIndent = str_repeat("\t", $indent);
		$output = '';
		if (is_array($src) || $src instanceof ArrayAccess || $src instanceof Traversable){
			foreach($src as $key=> $value){
				if (!is_numeric($value)){
					if (is_array($value) || $value instanceof ArrayAccess || $value instanceof Traversable){
						$value = UStr::jsonify($value, $indent+1, false);
					} else {
						if (is_object($value) && method_exists($value, '__toString')){
							$value = $value->__toString($indent+1);
						} else {
							$value = '"'.$escape($value).'"';
						}
					}
				}
				if (!empty($output)){
					$output .= ",\n";
				}
				$output .= "{$stIndent}\t\"{$key}\": {$value}";
			}
			$output = ($indentFirstLine ? $indent : '') . "{\n{$output}\n{$stIndent}}";
		} else {
			$output = empty($src) ? '' : "'" . $escape($src) . "'";
			$output = $stIndent . '{' . $output . '}';
		}
		return $output;
	}

	/** 
	 * Make a string's first character lowercase.
	 * 
	 * @param $string The string to uppercase.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return The uppercased string.
	 */
	public static function lcfirst(string $string, ?string $encoding=null): string {
		if (static::$has_mb){
			return mb_lcfirst($string, $encoding);
		} else {
			return lcfirst($string);
		}
	}	
	
	/** 
	 * How many characters are in the passed string?
	 * 
	 * @param $in The string to be counted. 
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return integer
	 */
	public static function len(string $in, ?string $encoding=null):int {
		if (static::$has_mb){
			return mb_strlen($in, $encoding);
		} else {
			return strlen($in);
		}
	}

	/** 
	 *  @brief Strip whitespace from the beginning of a string.
	 * 
	 * Strip whitespace (or other characters) from the beginning of a string.
	 * Adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param $haystack The string to be stripped. 
	 * @param $needles - String containing a list of characters to be stripped. With .. it is possible to specify an incrementing range of characters.
	 * If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding - Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return - The trimmed result string.
	 */
	public static function ltrim(string $haystack, string $needles=null, ?string $encoding=null):string {
		if (null == $needles) {
			$needles = SPACE_NEEDLE;
		}
		if (empty($haystack)) return $haystack;
		if (static::$has_mb){
			if (function_exists('mb_ltrim')){ //new as of php 8.4
				return mb_ltrim($haystack, $needles, $encoding);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/(^[$needles]+)/u", '', $haystack);
			}
		} else {
			return ltrim($haystack, $needles);
		}
	}

	/** 
	 * Get code point of a character.
	/ * 
	 * @param $string The string whose first character's code point should be returned.
	 * @param $encoding - Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return 
	 */
	public static function ord(string $string, ?string $encoding=null):int|false {
		if (static::$has_mb){
			return mb_ord($string, $encoding);
		} else {
			return ord($string);
		}
	}

	/** 
	 * Pad a multibyte string to a certain length with another multibyte string.
	 * 
	 * @param $string The input string.
	 * @param $length If the value of length is negative, less than, or equal to the length of the input string, no padding takes place, and string will be returned.
	 * @parm pad_string The string to use as padding.
	 * @note The pad_string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
	 * @param $pad_type Optional argument pad_type can be STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH. By default STR_PAD_RIGHT.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return The padded string. 
	 */
	public static function pad(string $string, int $length, string $pad_string=' ', int $pad_type=STR_PAD_RIGHT, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_str_pad($string, $length, $pad_string, $pad_type, $encoding);
		} else {
			return str_pad($string, $length, $pad_string, $pad_type);
		}
	}
	
	/** 
	 * Parse GET/POST/COOKIE data and set global variable.
	 * 
	 * @param $string The urlencoded data.  
	 * @param $result An array containing decoded and character encoded converted values.
	 * @param $encoding - Currently not used, present for consistency
	 *
	 * @return With multibyte - true/false success/failure or without multibyte always true.
	 */
	public static function parse(string $string, array &$result, ?string $encoding=null):bool {
		if (static::$has_mb){
			 return mb_parse_str($string, $array);
		 } else {
			 parse_str($string, $array);
			 return true;
		 }
	}
	
	/** 
	 * Find the position of the first occurrence of a substring in a string
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - If specified, search will start this number of characters counted from the beginning of the string. If the offset is negative, the search will start this number of characters counted from the end of the string.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - Position number or false if not found as an int.
	 */
	public static function pos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = false;
		if (static::$has_mb){
			$output =  mb_strpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strpos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * Repeat a string.
	 * 
	 * @param $string The string to be repeated.
	 * @param $times  How many times to repeat it.
	 * @param $encoding - Unncessary for this method, only included to match other UStr methods.
	 *
	 * @return A string consisting of $string repeated $times times.
	 */
	public static function repeat(string $string, int $times, ?string $encoding=null):string {
		return str_repeat($string, $times);
	}
	
	/** 
	 * Replace all occurrences of the search string with the replacement string
	 * 
	 * @param $search - The string segment that is to be replaced.
	 * @param $replace - The segment that is to replace the search segment.
	 * @param $subject - The input string.
	 * @param $&count - If passed, this will be set to the number of replacements performed.
	 * @param $encoding - Currently not used, present for consistency
	 *
	 * @return - string
	 */
	public static function replace(array|string $search, array|string $replace, string|array $subject, int &$count=null, ?string $encoding=null):string|array {
		return str_replace($search, $replace, $subject, $count);
	}

	/** 
	 * Finds position of last occurrence of a string within another, case insensitive.
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - Where to begin the search.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - Position as an int or false if not found.
	 */
	public static function ripos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = null;
		if (static::$has_mb){
			$output =  mb_strripos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strripos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * Find the position of the last occurrence of a substring in a string
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - Where to begin the search.
	 * - If zero or positive, the search is performed left to right skipping the first offset bytes of the haystack.
	 * - If negative, the search starts offset bytes from the right instead of from the beginning of haystack. The search is performed right to left, searching for the first occurrence of needle from the selected byte.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - Position as an int or false if not found.
	 */
	public static function rpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = null;
		if (static::$has_mb){
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
	 * @param $haystack The string to be stripped.
	 * @param $needles List of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.	 * @return - string the trimmed result. 
	 */
	public static function rtrim(string $haystack, string $needles=null, ?string $encoding=null):string {
		if (null == $needles) {
			$needles = SPACE_NEEDLE;
		}
		if (empty($haystack)) return $haystack;
		if (static::$has_mb){
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
	 * Randomly shuffles a string.
	 * 
	 * @param $string The string to be shuffled.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return A new string made up of the input string's characters shuffled into a random order.
	 */
	public function shuffle(string $string, ?string $encoding=null):string {
		if (static::$has_mb){
			$output = '';
			$arString = static::split($string, 1, $encoding);
			while (0 < $chars = len($arString)){
				$i = 1 == $chars ? 0 : rand(0, $chars-1);
				$output .= $arString[$i];
				array_splice($arString, $i, 1);
			}
			return $output;
		} else {
			return str_shufle($string);
		}
	}
	
	/** 
	 * Split a string into an array by character count.
	 * 
	 * @param $string String to be split.
	 * @param $length Charachter length of split sections.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - array of strings
	 */
	public static function split(string $string, int $length=1, ?string $encoding=null): array {
		if (static::$has_mb){
			return mb_str_split($string, $length, $encoding);
		} else {
			return str_split($string, $length);
		}
	}
	
	/** 
	 * Does the haystack string begin with the contents of the needle string?
	 * 
	 * @param $haystack String to be searched.
	 * @param $needle String or character to search for.
	 * @param $caseSensitive False to ignore case.
	 * @param $encoding Currently not used, present for consistency.
	 *
	 * @return bool
	 */
	public static function starts_with(string $haystack, string $needle, bool $caseSensitive=true, ?string $encoding=null):bool {
		if (!$caseSensitive){
			return static::starts_with(static::toLower($haystack), static::toLower($needle));
		}
		if (function_exists('str_starts_with')){
			return str_starts_with($haystack, $needle);
		} else {
			$len_needle = static::len($needle);
			if (0 == $len_needle){
				return true;
			} else {
				if (static::len($haystack) < $len_needle) {
					return false;
				} else {
					return static::substr($haystack, 0, $len_needle) == $needle;
				}	
			}
		}
	}

	/** 
	 * Strip HTML and PHP tags from a string.
	 *
	 * @param $string The string.
	 * @param $allowed_tags You can use the optional second parameter to specify tags which should not be stripped. These are either given as string, or as of PHP 7.4.0, as array. Refer to the example below regarding the format of this parameter.
	 * @param $encoding Does not currently do anything, present only for consistency.
	 * 
	 * @return 
	 */
	public static function strip_tags(string $string, array|string|null $allowed_tags=null, ?string $encoding=null):string {
		return strip_tags($string, $allowed_tags);
	}

	/** 
	 * Un-quote string quoted with addcslashes().
	 * 
	 * @param string The string to be unescaped.
	 * @param encoding Not currently in use, here for consistency.
	 * 
	 * @return 
	 */
	public static function stripcslashes(string $string, ?string $encoding = null){
		$output = $string;
		if (static::$has_mb)
		{
			while(mb_ereg("\\\U[0-9a-fA-F]{8}|\\\u[0-9a-fA-F]{4}", $output, $match)){
				$replacement = static::chr(hexdec(substr($match[0], 2)));
				$output = static::replace($match[0], $replacement, $output);
			}
		}
		$output = stripcslashes($output);
		return $output;
	}

	/** 
	 * Return part of a string
	 * 
	 * @param $in - String to take a substring of.
	 * @param $offset - Where to start the substring.
	 * - If offset is non-negative, the returned string will start at the offset'th position in string, counting from zero. For instance, in the string 'abcdef', the character at position 0 is 'a', the character at position 2 is 'c', and so forth.
	 * - If offset is negative, the returned string will start at the offset'th character from the end of string.
	 * - If string is less than offset characters long, an empty string will be returned.
	 * @param $length - Length of substring.
	 * - If length is given and is positive, the string returned will contain at most length characters beginning from offset (depending on the length of string).
	 * - If length is given and is negative, then that many characters will be omitted from the end of string (after the start position has been calculated when a offset is negative). If offset denotes the position of this truncation or beyond, an empty string will be returned.
	 * - If length is given and is 0, an empty string will be returned.
	 * - If length is omitted or null, the substring starting from offset until the end of the string will be returned
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - string
	 */
	public static function substr(string $in, int $offset, ?int $length=null, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_substr($in, $offset, $length, $encoding);
		} else {
			return substr($in, $offset, $length);
		}
	}

	/** 
	 * @brief Convert string to list of characters.
	 * Converts a string to an array of characters.  Intereprets ".." between characters as a range. Converts escape sequences to their characters.
	 *
	 * @param string The string.
	 * @param encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return An array of characters.
	 */
	public static function toCharList(string $string, ?string $encoding = null):array {
		$output = array();
		$string = static::stripcslashes($string, $encoding);
		$length = static::len($string, $encoding);
		for ($i = 0; $i < $length; $i++){
			$char = static::substr($string, $i, 1, $encoding);
			if ($i < ($length-4) && '..' == static::substr($string, $i+1, 2, $encoding)){
				$endChar = static::substr($string, $i+3, 1, $encoding);
				$i += 3;
				$ordStart = static::ord($char, $encoding);
				$ordEnd = static::ord($endChar, $encoding);
				if ($ordStart > $ordEnd){
					$bucket = $ordEnd;
					$ordEnd = $ordStart;
					$ordStart = $bucket;
				}
				for ($ord = $ordStart; $ord <= $ordEnd; $ord++){
					$output[] = static::chr($ord, $encoding);
				}
			} else {
				$output[] = $char;
			}
		}
		$output = array_unique($output);
		return $output;
	}
	
	/** 
	 * Generate a lowercase version of the string
	 * 
	 * @param $in - Input string to be converted.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return Lowercased output string.
	 */
	public static function toLower(string $in, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_strToLower($in, $encoding);
		} else {
			return strToLower($in);
		}
	}

	/** 
	 * Generate an uppercase version of the string
	 * 
	 * @param $in Input string.
	 * @param $encoding - Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return - Uppercased output string.
	 */
	public static function toUpper(string $in, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_strToUpper($in, $encoding);
		} else {
			return strTUpper($in);
		}
	}


	/** 
	 *  @brief Strip whitespace from the beginning and end of a string.
	 *
	 * Strip whitespace (or other characters) from the beginning and end of a string.
	 * Solution adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param $haystack String to be trimmed. 
	 * @param $needles - List of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return - The trimmed result string.
	 */
	public static function trim(string $haystack, string $needles=null, ?string $encoding=null):string {
		if (null == $needles) {
			$needles = SPACE_NEEDLE;
		}
		if (empty($haystack)) return $haystack;
		if (static::$has_mb){
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

	/** 
	 * Make a string's first character uppercase.
	 * 
	 * @param $string The string to uppercase.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return The uppercased string.
	 */
	public static function ucfirst(string $string, ?string $encoding=null): string {
		if (static::$has_mb){
			return mb_ucfirst($string, $encoding);
		} else {
			return ucfirst($string);
		}
	}
	
	/** 
	 * Make the first character of each word in a string uppercase.
	 * 
	 * @param $string The string to uppercase.
	 * @param $separators - List of characters to be considered whitespace that may separate words.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return The uppercased string.
	 */
	public static function ucwords(string $string, ?string $separators=null, ?string $encoding=null): string {
		if (null == $separators) {
			$separators = SPACE_NEEDLE;
		}
		if (static::$has_mb){
			$output = '';
			for ($i = 0; $i < mb_strlen($string); $i++){
				$prev = 0 == $i ? ' ' : mb_substr($string, $i-1, 1, $encoding);
				$cur = mb_substr($string, $i, 1, $encoding);
				$output .= false === mb_strpos($separators, $prev, 0, $encoding) ? $cur : mb_strtoupper($cur, $encoding);
			}
			return $output;
		} else {
			return ucwords($string);
		}
	}	
	
}
?>
