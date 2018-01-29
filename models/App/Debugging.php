<?php
namespace App;

class Debugging
{
    private static $consoleBuffer = '';
    private static $propelDebug = null;

	public static $logger = null;
	public static $app = null;
    public static $traceIgnores = [];


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


    public static function init($app, $logger)
    {
        self::$app = $app;
        self::$logger = $logger;
        self::$propelDebug = \Propel\Runtime\Propel::getConnection();
		self::$propelDebug->useDebug(true);
        register_shutdown_function('\App\Debugging::destruct');
    }

    public static function destruct()
    {
        if (!self::$app) { return false; }
        $file = getenv('APPLOGS') . '/console_' . self::$app . '.txt';
        file_put_contents($file, self::$consoleBuffer . "\n", FILE_APPEND);
    }

	public static function seeLastQuery()
	{
		return $propel_debug->getLastExecutedQuery();
	}

    /**
     * Format an Exception message that shows the passed in message, the exception message, the line, line number,
     * and some trace. This can then be passed on to whatever error handling the developer wants.
     */
    public static function formatException($message, $exception)
    {
        $separator = "<br >\n\t\t\t";
        $trace = $exception->getTraceAsString();
        $trace = preg_split('/(?:^|\s)#[0-9]+\s/', $trace); // The very first substring ("^#0 ") is also included as a delimiter so it is removed along with the others. As a consequence, the resulting array starts with an empty element.
        $filtered = array();
        $count = 0;
        foreach ($trace as $tr) {
            if ($tr == '') { continue; }
            $count += 1;
            foreach (self::$traceIgnores as $ignore) {
                if (strpos($tr, $ignore) !== false) { continue 2; }
                if (strpos($tr, str_replace('/', '\\', $ignore)) !== false) { continue 2; }
            }
            $filtered[] = "#$count $tr";
        }
        return "$message{$separator}{$exception->getMessage()}{$separator}In file: {$exception->getFile()}{$separator}Line: {$exception->getLine()}{$separator}Trace:{$separator}".implode($separator, $filtered)."{$separator}";
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
        self::$consoleBuffer .= "<pre style=\"margin:0px;\">$message</pre>";
	}

    public static function vardump(&$var)
    {
        ob_start();
        var_dump($var);
        return ob_get_clean();
    }
}
