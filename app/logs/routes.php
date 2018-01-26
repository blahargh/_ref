<?php

$app->route('get', '{app}', function ($args) {
    $app = $args['app'];
    $dir = getenv('APPLOGS') . '/' . $app;
	$fileWalker = new \IMP\FileWalker($dir);
	$logFiles = array();
	while ($fileWalker->walk()) {
		if (substr($fileWalker->filename, -4) === '.log') {
			$logFiles[] = $fileWalker->filename;
		}
	}
	arsort($logFiles);

    $this->render('logfiles.html', [
        'app' => $app,
        'logFiles' => $logFiles,
    ]);
});


$app->route('get', '{app}/{file}', function ($args) {
    $app = $args['app'];
    $file = $args['file'];
    $dir = getenv('APPLOGS') . '/' . $app;
	$lines = !empty($this->getParam('lines')) && $this->getParam('lines') > 0 ? $this->getParams('lines') + 0 : 50;
	$contents = tailCustom("$dir/$file", $lines);
	$lines = preg_split('/(^|\n)date=/', trim($contents));
	$logData = array();
	foreach ($lines as $line) {
		if ($line == '') { continue; } // Because of the preg_split, the left side of the first line is also returned, which should be empty, so get rid of it.
		$line = 'date=' . $line; // Add the beginning string back on.
		preg_match_all('/(\w+)=((?:\"[^\"]*\")|(?:[^\s]*))/', $line, $matches);
		$dataset = array();
		foreach ($matches[1] as $i => $name) {
			$dataset[$name] = trim($matches[2][$i], " \n\r\t\"");
		}
        if (isset($dataset['trace'])) {
            $dataset['trace'] = str_replace('\t#', "\n\t\t#", $dataset['trace']);
        }
		$logData[] = $dataset;
	}
	$logData = array_reverse($logData);

    $this->render('logs.html', [
        'app' => $app,
        'file' => $file,
        'logData' => print_r($logData, true),
    ]);
});


/**
 * Source: https://gist.github.com/lorenzos/1711e81a9162320fde20
 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
 * @author Torleif Berger, Lorenzo Stanco
 * @link http://stackoverflow.com/a/15025877/995958
 * @license http://creativecommons.org/licenses/by/3.0/
 */
function tailCustom($filepath, $lines = 1, $adaptive = true) {
	// Open file
	$f = @fopen($filepath, "rb");
	if ($f === false) return false;
	// Sets buffer size, according to the number of lines to retrieve.
	// This gives a performance boost when reading a few lines from the file.
	if (!$adaptive) $buffer = 4096;
	else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
	// Jump to last character
	fseek($f, -1, SEEK_END);
	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';
	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {
		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);
		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);
		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;
		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}
	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	// Close file and return
	fclose($f);
	return trim($output);
}
