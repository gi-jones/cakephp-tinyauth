<?php
App::uses('CakeNumber', 'Utility');

/**
 * Extend CakeNumber with a few important improvements:
 * - config setting for format()
 * - spacer char for currency (initially from https://github.com/cakephp/cakephp/pull/1148)
 * - signed values possible
 *
 * 2011-03-07 ms
 */
class NumberLib extends CakeNumber {

	protected static $_currency = 'EUR';

	protected static $_symbolRight = '€';

	protected static $_symbolLeft = '';

	protected static $_decimals = ',';

	protected static $_thousands = '.';

	/**
	 * Correct the defaul values according to localization
	 *
	 * @return void
	 */
	public static function config($options = array()) {
		$config = $options + (array)Configure::read('Localization');
		foreach ($config as $key => $value) {
			$key = '_' . $key;
			if (!isset(self::${$key})) {
				continue;
			}
			self::${$key} = $value;
		}
	}

	/**
	 * Display price (or was price if available)
	 * Without allowNegative it will always default all non-positive values to 0
	 *
	 * @param price
	 * @param specialPrice (outranks the price)
	 * @param options
	 * - places
	 * - allowNegative (defaults to false - price needs to be > 0)
	 *
	 * @deprecated use currency()
	 * @return string
	 * 2011-07-30 ms
	 */
	public static function price($price, $specialPrice = null, $formatOptions = array()) {
		if ($specialPrice !== null && $specialPrice > 0) {
			$val = $specialPrice;
		} elseif ($price > 0 || !empty($formatOptions['allowNegative'])) {
			$val = $price;
		} else {
			if (isset($formatOptions['default'])) {
				return $formatOptions['default'];
			}
			$val = max(0, $price);
		}
		return self::money($val, $formatOptions);
	}

	/**
	 * Convenience method to display the default currency
	 *
	 * @return string
	 * 2011-10-05 ms
	 */
	public static function money($amount, $formatOptions = array()) {
		return self::currency($amount, null, $formatOptions);
	}

	/**
	 * format numeric values
	 * should not be used for currencies
	 *
	 * @param float $number
	 * @param integer $places (0 = int, 1..x places after dec, -1..-x places before dec)
	 * @param array $option : currency=true/false, ... (leave empty for no special treatment)
	 * //TODO: automize per localeconv() ?
	 * 2009-04-03 ms
	 */
	public static function format($number, $formatOptions = array()) {
		if (!is_numeric($number)) {
			$default = '---';
			if (!empty($options['default'])) {
				$default = $options['default'];
			}
			return $default;
		}
		if ($formatOptions === false) {
			$formatOptions = array();
		} elseif (!is_array($formatOptions)) {
			$formatOptions = array('places' => $formatOptions);
		}
		$options = array('before' => '', 'after' => '', 'places' => 2, 'thousands' => self::$_thousands, 'decimals' => self::$_decimals, 'escape' => false);
		$options = array_merge($options, $formatOptions);

		if (!empty($options['currency'])) {
			if (!empty(self::$_symbolRight)) {
				$options['after'] = ' ' . self::$_symbolRight;
			} elseif (!empty(self::$_symbolLeft)) {
				$options['before'] = self::$_symbolLeft . ' ';
			}
		}

		/*
		if ($spacer !== false) {
			$spacer = ($spacer === true) ? ' ' : $spacer;
			if ((string)$before !== '') {
				$before .= $spacer;
			}
			if ((string)$after !== '') {
				$after = $spacer . $after;
			}
		}

		*/
		if ($options['places'] < 0) {
			$number = round($number, $options['places']);
		}
		$sign = '';
		if ($number > 0 && !empty($options['signed'])) {
			$sign = '+';
		}
		if (isset($options['signed'])) {
			unset($options['signed']);
		}
		return $sign . parent::format($number, $options);
	}

	/**
	 * Correct the default for European countries
	 * 2012-04-08 ms
	 */
	public static function currency($number, $currency = null, $formatOptions = array()) {
		if ($currency === null) {
			$currency = self::$_currency;
		}
		$defaults = array();
		if ($currency !== 'EUR' && isset(self::$_currencies[$currency])) {
			$defaults = self::$_currencies[$currency];
		} elseif ($currency !== 'EUR' && is_string($currency)) {
			$defaults['wholeSymbol'] = $currency;
			$defaults['wholePosition'] = 'before';
			$defaults['spacer'] = true;
		}
		$defaults += array(
			'wholeSymbol' => '€', 'wholePosition' => 'after',
			'negative' => '-', 'positive'=> '+', 'escape' => true,
			'decimals' => ',', 'thousands' => '.',
			'spacer' => $currency === 'EUR' ? true : false
		);
		$options = array_merge($defaults, $formatOptions);

		if (!empty($options['spacer'])) {
			$spacer = is_string($options['spacer']) ? $options['spacer'] : ' ';

			if ($options['wholePosition'] === 'after') {
				$options['wholeSymbol'] = $spacer . $options['wholeSymbol'];
			} elseif ($options['wholePosition'] === 'before') {
				$options['wholeSymbol'] .= $spacer;
			}
		}

		$sign = '';
		if ($number > 0 && !empty($options['signed'])) {
			$sign = $options['positive'];
		}
		return $sign . parent::currency($number, null, $options);
	}

	/**
	 * Formats a number with a level of precision.
	 *
	 * @param float $number	A floating point number.
	 * @param integer $precision The precision of the returned number.
	 * @param string $decimals
	 * @return float Formatted float.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
	 */
	public static function precision($number, $precision = 3, $decimals = '.') {
		$number = parent::precision($number, $precision);
		if ($decimals !== '.' && $precision > 0) {
			$number = str_replace('.', $decimals, $number);
		}
		return $number;
	}

	/**
	 * Returns a formatted-for-humans file size.
	 *
	 * @param integer $size Size in bytes
	 * @return string Human readable size
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
	 */
	public static function toReadableSize($size, $decimals = '.') {
		$size = parent::toReadableSize($size);
		if ($decimals !== '.') {
			$size = str_replace('.', $decimals, $size);
		}
		return $size;
	}

	/**
	 * Formats a number into a percentage string.
	 *
	 * @param float $number A floating point number
	 * @param integer $precision The precision of the returned number
	 * @param string $decimals
	 * @return string Percentage string
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
	 */
	public static function toPercentage($number, $precision = 2, $decimals = '.') {
		return self::precision($number, $precision, $decimals) . '%';
	}

	/**
	 * get the rounded average
	 * @param array $values: int or float values
	 * @param integer $precision
	 * @return int $average
	 * 2009-09-05 ms
	 */
	public static function average($values, $precision = 0) {
		$average = round(array_sum($values) / count($values), $precision);
		return $average;
	}

	/**
	 * Round value
	 *
	 * @param float $number
	 * @param float $increment
	 * @return float $result
	 * 2011-04-14 lb
	 */
	public static function roundTo($number, $increments = 1.0) {
		$precision = self::getDecimalPlaces($increments);
		$res = round($number, $precision);
		if ($precision <= 0) {
			$res = (int)$res;
		}
		return $res;
	}

	/**
	 * Round value up
	 *
	 * @param float $number
	 * @param integer $increment
	 * @return float $result
	 * 2011-04-14 lb
	 */
	public static function roundUpTo($number, $increments = 1) {
		return (ceil($number / $increments) * $increments);
	}

	/**
	 * Round value down
	 *
	 * @param float $number
	 * @param integer $increment
	 * @return float $result
	 * 2011-04-14 lb
	 */
	public static function roundDownTo($number, $increments = 1) {
		return (floor($number / $increments) * $increments);
	}

	/**
	 * Get decimal places
	 *
	 * @param float $number
	 * @return int $decimalPlaces
	 * 2011-04-15 lb
	 */
	public static function getDecimalPlaces($number) {
		$decimalPlaces = 0;
		while ($number > 1 && $number != 0) {
			$number /= 10;
			$decimalPlaces -= 1;
		}
		while ($number < 1 && $number != 0) {
			$number *= 10;
			$decimalPlaces += 1;
		}
		return $decimalPlaces;
	}

	/**
	 * Returns the English ordinal suffix (th, st, nd, etc) of a number.
	 *
	 * echo 2, Num::ordinal(2); // "2nd"
	 * echo 10, Num::ordinal(10); // "10th"
	 * echo 33, Num::ordinal(33); // "33rd"
	 *
	 * @param integer number
	 * @return string
	 */
	public static function ordinal($number) {
		if ($number % 100 > 10 and $number % 100 < 14) {
			return 'th';
		}
		switch ($number % 10) {
			case 1:
				return 'st';
			case 2:
				return 'nd';
			case 3:
				return 'rd';
			default:
				return 'th';
		}
	}

	/**
	 * Can compare two float values
	 *
	 * @link http://php.net/manual/en/language.types.float.php
	 * @param float $x
	 * @param float $y
	 * @param float $precision
	 * @return boolean
	 */
	public static function isFloatEqual($x, $y, $precision = 0.0000001) {
		return ($x + $precision >= $y) && ($x - $precision <= $y);
	}

	/**
	 * Get the settings for a specific formatName
	 *
	 * @param string $formatName (EUR, ...)
	 * @return array $currencySettings or null on failure
	 */
	public static function getFormat($formatName) {
		if (!isset(self::$_currencies[$formatName])) {
			return null;
		}
		return self::$_currencies[$formatName];
	}

}
