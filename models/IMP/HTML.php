<?php
namespace IMP;

/**
 * Utility class for generating HTML code.
 * This is made static since it's methods never modify anything and needs to
 * be available globally.
 */
class HTML
{
	private static $start_stopwatch = 0;
	
	/**
	 * Returns an easy-to-read format of an array.
	 */
	public static function outArray ($arr, $label = '') {
		return '<pre>'.($label?$label.' = ':'').print_r($arr, true).'</pre>';
	}
	
	/**
	 * Redirect to a different page using an HTTP header with 302 response code.
	 *
	 * @param String $url to redirect to.
	 */
	public static function redirect($url)
	{
		if (empty($url)) { $url = '/'; }
		header('Location: ' . $url, true, 302);
		#header("refresh:1;url=$url");
		#header('Location: ' . $url);
		/*
		print '
			<script>
				window.location = "' . $url . '";
			</script>
		';
		*/
		exit;
	}
	
	/**
	 * A helper function that only checks if an input exists. It will first
	 * check POST, then GET. If it exists in either, return TRUE, FALSE otherwise.
	 *
	 * NOTE: This does not care what the input value is. Even if the value is
	 * false, or falsey, as long as it actually exists, this method will
	 * return TRUE.
	 *
	 * @param String $name of the POST or GET input to check.
	 *
	 * @return Bool TRUE if found, FALSE otherwise.
	 */
	public static function checkInput($name)
	{
		return isset($_POST[$name]) || isset($_GET[$name]);
	}
	
	/**
	 * A helper function that just checks and retrieves if an input exists. It will first check POST,
	 * then GET if not found in POST. If not found in either, $default will be returned.
	 * This reduces the need to have to check for the existence of an input variable
	 * before using it.
	 *
	 * SECURITY NOTE: This does not encode the input value.
	 *
	 * @param String $name of the POST or GET input.
	 * @param Mixed $default (optional) value returned if the input does not exists.
	 *
	 * @return String containing the value of the input if it exists, NULL otherwise.
	 */
	public static function getInput($name, $default = null)
	{
		if (isset($_POST[$name])) { return $_POST[$name]; }
		elseif (isset($_GET[$name])) { return $_GET[$name]; }
		else { return $default; }
	}
	
	/**
	 * Concatenate parts into a well-formed file/directory of the form, "/abc/defghi".
	 */
	public static function makePath()
	{
		$parts = func_get_args();
		foreach ($parts as $i => $part) {
			$parts[$i] = trim(str_replace('\\', '/', $part), '/');
		}
		return '/' . implode('/', array_filter($parts));
	}
	
	/**
	 * This function will encode certain characters. Mainly used for encoding
	 * input values to prevent code-injections.
	 *
	 * @param Mixed $mixed is the string or array of values to encode.
	 * @param Array $options (optional) contains settings to change the method's behavior. Currently, only "decode" is used if it's set to TRUE or 1.
	 * @param Array $translation_table (optional) is a custom translation table if desired. This should be a name-value pair array where name is the character to encode and value is what to encode it to.
	 *
	 * @return Array containing the encoded value if passed a string, an array of encoded values if passed as an array with their corresponding keys.
	 */
	public static function encode($mixed, $options = array(), $translation_table = null)
	{
		if (!$translation_table) {
			$translation_table = array(
				 '<'  => '&lt;'
				,'>'  => '&gt;'
				,'®'  => '&reg;'
				,'™'  => '&trade;'
				,'©'  => '&copy;'
				,'|'  => '&#124;'
				,'"'  => '&quot;'
				,'“'  => '&ldquo;'
				,'”'  => '&rdquo;'
				,'`'	=> '&#096;'
				,"'"  => '&#039;'
			);
		}
		if (is_object($mixed)) {
			# Leave objects alone?
		} elseif (is_array($mixed)) {
			foreach ($mixed as $i=>$v) {
				unset($mixed[$i]); #<-- Remove unencoded element.
				$mixed[self::encode($i, $options)] = self::encode($v, $options);
			}
		} else {
			#$mixed = stripslashes(trim($mixed)); #<-- Padding certain chars through POST automatically are given a slash by PHP, like double quotes.
			$mixed = trim($mixed);
			if (!empty($options['decode'])) {
				$mixed = strtr($mixed, array_flip($translation_table));
				#WARNING# $mixed = html_entity_decode($str, ENT_NOQUOTES, 'UTF-8'); #<-- Do not decode. Leave "&#frac12;" as is and let the browser interpret it, otherwise it changes to the "1/2" character which doesn't get displayed properly on the browser (diamond question mark).
			} else {
				$mixed = strtr($mixed, $translation_table);
				$mixed = htmlentities($mixed, ENT_SUBSTITUTE | ENT_QUOTES, 'UTF-8'); #<-- Encode all other characters that have HTML character entity equivalents, like the fraction "1/2".
				$mixed = str_replace('&amp;', '&', $mixed); #<-- Correct the "&" convertion done by htmlentities().
			}
		}
		return $mixed;
	}
	
	/**
	 * StartStopwatch() along with EndStopwatch() can be used to determine
	 * the process time between the two function calls.
	 */
	public static function startStopwatch()
	{
		self::$start_stopwatch = microtime();
	}
	
	/**
	 * EndStopwatch() along with StartStopwatch() can be used to determine
	 * the process time between the two function calls.
	 *
	 * @return String in HH:MM::SS.uS format from when StartStopwatch() was
	 *   last called, to when this method was called.
	 */
	public static function endStopwatch($returnUnformatted = false)
	{
		$end_stopwatch = microtime();
		list($start_usec, $start_sec) = explode(' ', self::$start_stopwatch);
		list($end_usec, $end_sec) = explode(' ', $end_stopwatch);
		
		$start = $start_sec . substr($start_usec, 1);
		$end = $end_sec . substr($end_usec, 1);
		$diff = bcsub($end, $start, 8);
		if ($returnUnformatted) { return $diff; };
		
		list($sec, $usec) = explode('.', $diff);
		
		$min = floor($sec / 60);
		$sec = $sec - ($min * 60);
		$hrs = floor($min / 60);
		$min = $min - ($hrs * 60);
		return sprintf("%02dh %02dm %02d.%ss", $hrs, $min, $sec, $usec);
	}
	
	/**
	 * Format a microtime using the HMS format.
	 */
	public static function formatMicrotime($microtime)
	{
		if (is_string($microtime)) {
			// The PHP function microtime() was used without passing TRUE as an argument (PHP 5), which makes it return a float instead of a string.
			list($usec, $sec) = explode(' ', $microtime);
			$microTime = (float)$usec + (float)$sec;
		}
		
		list($sec, $usec) = explode('.', $microtime);
		$min = floor($sec / 60);
		$sec = $sec - ($min * 60);
		$hrs = floor($min / 60);
		$min = $min - ($hrs * 60);
		return sprintf("%02dh %02dm %02d.%ss", $hrs, $min, $sec, $usec);
	}
	
	public static function datePHP($timeStamp=NULL, $delim='/'){ return date('m'.$delim.'d'.$delim.'Y', $timeStamp); }
	public static function dateSQL($timeStamp=NULL, $delim='-'){ return date('Y'.$delim.'m'.$delim.'d', $timeStamp); }
	public static function timePHP($timeStamp=NULL, $delim=':'){ return date('H'.$delim.'i'.$delim.'s', $timeStamp); }
	public static function timeSQL($timeStamp=NULL, $delim=':'){ return date('H'.$delim.'i'.$delim.'s', $timeStamp); }
	public static function dateTimePHP($timeStamp=NULL, $delimD='/', $delimT=':'){ return date('m'.$delimD.'d'.$delimD.'Y H'.$delimT.'i'.$delimT.'s', $timeStamp); }
	public static function dateTimeSQL($timeStamp=NULL, $delimD='-', $delimT=':'){ return date('Y'.$delimD.'m'.$delimD.'d H'.$delimT.'i'.$delimT.'s', $timeStamp); }

	public static function datePHPToSQL($dataStr)
	{
		$res = preg_replace('/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{1,4})(.*)/', "$3-$1-$2", $dataStr);
		return $res;
	}
	public static function dateSQLToPHP($dataStr)
	{
		$res = preg_replace('/([0-9]{1,4})\-([0-9]{1,2})\-([0-9]{1,2})(.*)/', "$2/$3/$1", $dataStr);
		return $res;
	}
	public static function dateTimePHPToSQL($dataStr)
	{
		$res = preg_replace('/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{1,4}) (.*)/', "$3-$1-$2 $4", $dataStr);
		return $res;
	}
	public static function dateTimeSQLToPHP($dataStr)
	{
		$res = preg_replace('/([0-9]{1,4})\-([0-9]{1,2})\-([0-9]{1,2}) (.*)/', "$2/$3/$1 $4", $dataStr);
		return $res;
	}
	
	/**
	 * Send email.
	 */
	public static function sendEmail($from, $to, $subject, $message, $cc='', $bcc='')
	{
		//$headers  = "From: $from\r\n";
		//$headers .= "Reply-To: $from\r\n";
		//$headers .= "X-Mailer: PHP/".phpversion();
		$to = str_replace(array("\r","\n"), array('INVALID','ERROR'), $to);
		$message  = "<html><head><title>$subject</title></head><body>".str_replace(array("\r", "\n"), array('', '<br />'), $message).'</body></html>';
		$headers  = 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n";
		$headers .= "From: {$from}\r\n";
		if($cc){ $headers .= "Cc: {$cc}\r\n"; }
		if($bcc){ $headers .= "Bcc: {$bcc}\r\n"; }
		$headers .= "Reply-To: {$from}\r\n";
		$headers .= "X-Mailer: PHP/".phpversion();
		mail($to, $subject, $message, $headers);
	}
	
	/**
	 * Money format.
	 */
	public static function formatMonetary($float)
	{
		$float += 0;
		if ($float<0) {
			$value = sprintf("%0.2f", $float*-1);
			$value = '-$' . number_format($value, 2, '.', ',');
		}else{
			$value = sprintf("%0.2f", $float);
			$value = '$' . number_format($value, 2, '.', ',');
		}
		return $value;
	}
	
	/**
	 * Rounded significant figures.
	 */
	public static function roundDigits($value, $precision, $suffix='')
	{
		$number = $value + 0;
		if (is_string($value)) {
			$substring = substr($value, strlen($number));
			$suffix = $substring . $suffix;
		}
		return sprintf("%0.0$precision" . 'f', $number) . $suffix;
	}
	
	/**
	 * Remove the section indentation of a multi-line string.
	 */
	public static function removeIndentation($str, $offset = 0)
	{
		$str = ltrim($str, "\r\n");
		$str = rtrim($str, " \t\r\n");
		$indentation = strspn($str, " \t");
		$indentation -= $offset;
		if ($indentation > 0) { $str = str_replace(substr($str, 0, $indentation), '', $str); }
		return $str;
	}
	
	/**
	 * Indent a multi-line string.
	 * $indentation can be numeric or a string. If numeric, that number of tabs will be used.
	 * If it is a string, then that string will be used.
	 */
	public static function indent($str, $indentation = 0, $skipFirstLine = false)
	{
		if (is_numeric($indentation)) {
			$str = str_replace("\n", "\n" . str_repeat("\t", $indentation), $str);
			if (!$skipFirstLine) {
				$str = str_repeat("\t", $indentation) . $str;
			}
		} else {
			$str = str_replace("\n", "\n" . $indentation, $str);
			if (!$skipFirstLine) {
				$str = $indentation . $str;
			}
		}
		return $str;
	}
}
?>