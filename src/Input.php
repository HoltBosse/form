<?php
namespace HoltBosse\Form;

class Input {
	static public function stringURLSafe(string $string): string {
		//lowercase the string
		$str = strtolower($string);

		//remove any '-' from the string they will be used as concatonater
		$str = str_replace('-', ' ', $str);
		$str = str_replace('_', ' ', $str);

		//trim any whitespace from the start and end of the string
		$str = trim($str);
		
		// remove any duplicate whitespace, and ensure all characters are alphanumeric
		$str = preg_replace(['/\s+/','/[^A-Za-z0-9\-]/'], ['-',''], $str);

		// lowercase and trim
		$str = trim(strtolower($str));
		return $str;
	}

	//this method exists so that if any future improvements are to be made, it is easy to do in one place
	static public function stringHtmlSafe(string $string): string {
		//for older php versions that convert only double quotes, we want to match modern php
		return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
	}

	static public function makeAlias(string $string): string {
		$string = strip_tags($string);
		$string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string); // Remove low ASCII chars
		$string = Input::stringURLSafe($string);
		return $string;
	}

	static public function tuplesToAssoc(mixed $arr): mixed {
		if (is_array($arr)) {
			$result = [];
			foreach ($arr as $i) {
				if ($i['value'] !== false && $i['value'] !== null && $i['value'] !== '') {
					$result[$i['key']] = $i['value'];
				}
			}
			return $result;
		} else {
			return [];
		}
	}

	public static function getVar(mixed $input, string $filter='RAW', mixed $default=NULL): mixed {
		if (isset($_GET[$input])) {
			return Input::filter($_GET[$input], $filter);
		} elseif (isset($_POST[$input])) {
			return Input::filter($_POST[$input], $filter);
		} else {
			if ($default!==NULL) {
				return $default;
			} else {
				return NULL;
			}
		}
	}

	public static function filter(mixed $input, string $filter='RAW'): mixed {
		$foo=$input;
		if ($filter=="RAW") {
			return $foo;
		} elseif ($filter=="ALIAS") {
			$temp = filter_var($foo, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
			return Input::stringURLSafe($temp);
		} elseif ($filter=="TEXTAREA") {
			// replace newlines with placeholder
			$foo = str_replace("\n","[NEWLINE]",$foo);
			return filter_var($foo, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		} elseif ($filter=="USERNAME"||$filter=="TEXT"||$filter=="STRING") {
			return htmlspecialchars($foo, ENT_QUOTES);
		} elseif ($filter=="EMAIL") {
			return filter_var($foo, FILTER_VALIDATE_EMAIL);
		} elseif ($filter=="URL") {
			return filter_var($foo, FILTER_VALIDATE_URL);
		} elseif ($filter=="ARRAYRAW") {
			if (!is_array($foo)) {
				return false;
			}
			return $foo;
		} elseif ($filter=="CSVINT") {
			$temparr = explode(",",$foo);
			$ok = true;
			foreach ($temparr as $temp) {
				if (!is_numeric($temp)) {
					return false;
				}
			}
			return $foo;
		} elseif ($filter=="ARRAYTOJSON"||$filter=="ARRAY") {
			if (!is_array($foo)) {
				return false;
			}
			$json = json_encode($foo);
			return $json;
		} elseif ($filter=="ARRAYOFINT"||$filter=="ARRAYNUM") {
			if (is_array($foo)) {
				return $foo;
			} else {
				return false;
			}
		} elseif ($filter=="ARRAYOFSTRING") {
			if (is_array($foo)) {
				$ok = true;
				foreach ($foo as $bar) {
					if (is_string($bar)) {
						// this one is fine
					} else {
						$ok = false;
					}
				}
				if ($ok) {
					return $foo;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} elseif ($filter=="ARRAYOFARRAYS") {
			if (is_array($foo)) {
				$ok = true;
				foreach ($foo as $bar) {
					if (is_array($bar)) {
						// this one is fine
					} else {
						$ok = false;
					}
				}
				if ($ok) {
					return $foo;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} elseif ($filter=="NUM"||$filter=="INT"||$filter=="NUMBER"||$filter=="NUMERIC") {
			if ($foo===0) {
				return 0;
			} else {
				return filter_var($foo, FILTER_SANITIZE_NUMBER_INT);
			}
		}
		elseif ($filter=="FLOAT") {
			if ($foo===0) {
				return 0;
			} else {
				return filter_var($foo, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
			}
		}
		elseif ($filter=="JSON") {
			return json_decode($foo) ? $foo : false;
		} else {
			return false;
		}
	}
}