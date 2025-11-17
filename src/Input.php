<?php
namespace HoltBosse\Form;

use Respect\Validation\Validator;
use Respect\Validation\ChainedValidator;

class Input {
	public static function stringURLSafe($string) {
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
	public static function stringHtmlSafe($string) {
		//for older php versions that convert only double quotes, we want to match modern php
		return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
	}

	public static function sprintfHtmlSafe($format, ...$args) {
		$safeArgs = array_map(function($arg) {
			if (is_string($arg)) {
				return self::stringHtmlSafe($arg);
			} elseif (is_object($arg) && method_exists($arg, '__toString')) {
				// Object with __toString, sanitize the string representation
				return self::stringHtmlSafe((string) $arg);
			} else {
				return $arg;
			}
		}, $args);
		return sprintf($format, ...$safeArgs);
	}

	public static function printfHtmlSafe($format, ...$args) {
		$output = self::sprintfHtmlSafe($format, ...$args);
		echo $output;
		return strlen($output);
	}

	public static function makeAlias($string) {
		$string = strip_tags($string);
		$string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string); // Remove low ASCII chars
		$string = Input::stringURLSafe($string);
		return $string;
	}

	public static function tuplesToAssoc($arr) {
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

	public static function buildValidatorFromArray(array $validators): Validator|ChainedValidator {
		$structureRule = Validator::arrayType()->each(Validator::arrayType())->call('array_keys', Validator::each(Validator::stringType()));

		if (!$structureRule->validate($validators)) {
			throw new \InvalidArgumentException(
				'Each validator name must be a string key with an array value (arguments).'
			);
		}

		$validator = Validator::alwaysValid();

		foreach ($validators as $validatorName => $args) {
			if (empty($args)) {
				$validator = $validator->{$validatorName}();
			} else {
				if (array_values($args) === $args) {
					$validator = $validator->{$validatorName}(...$args);
				} else {
					$validator = $validator->{$validatorName}(...array_values($args));
				}
			}
		}

		return $validator;
	}

	public static function getVar(mixed $input, null|string|Validator|ChainedValidator $filter='RAW', mixed $default=NULL) {
		if (isset($_GET[$input])) {
			return Input::filter($_GET[$input], $filter, $default);
		} elseif (isset($_POST[$input])) {
			return Input::filter($_POST[$input], $filter, $default);
		} else {
			return $default;
		}
	}

	public static function filter(mixed $input, null|string|Validator|ChainedValidator $filter='RAW', mixed $default=NULL) {
		$foo=$input;

		//use validator instance if it exists
		if(!is_string($filter) && is_object($filter)) {
			if($filter->isValid($foo)) {
				return $input;
			} else {
				return $default;
			}
		}

		//old terrible, trashy, broken junk for backwards compat
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