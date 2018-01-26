<?php
namespace App;

class Debugging
{
	public static $logger = null;
	public static $app = null;

	private $propel_debug = null;

	public function __construct() {
		$this->propel_debug = \Propel\Runtime\Propel::getConnection();
		$this->propel_debug->useDebug(true);
	}

	public function write($data)
	{
		print '<pre>'.print_r($data, true).'</pre>';
	}

	public function getLastQuery($print)
	{
		if ($print) {
			$this->write($this->propel_debug->getLastExecutedQuery());
		} else {
			return $this->propel_debug->getLastExecutedQuery();
		}
	}

	public static function seeLastQuery()
	{
		$propel_debug = \Propel\Runtime\Propel::getConnection();
		$propel_debug->useDebug(true);
		return $propel_debug->getLastExecutedQuery();
	}

	public static function log($debugMessage = null)
	{
        if ($debugMessage) {
            self::$logger->debug($debugMessage);
        } else {
            return self::$logger;
        }
	}

	public static function console($message)
	{
        if (!self::$app) { return false; }
        $file = getenv('APPLOGS') . '/console_' . self::$app . '.txt';
        file_put_contents($file, $message . "\n", FILE_APPEND);
	}
}
