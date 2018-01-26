<?php
namespace IMP;
/**
 * Docer is a simple program that will parse a PHP class file and generate a
 * reference documentation showing information provided in the comments within
 * the file that follows the PHPDoc standards.
 *
 * This is a class that can provide Docer capabilities. This is a "plugin"
 * because it's a simple port from one framework in order to be easily
 * used on a different framework, sacrificing visual customization for
 * efficiency and portability.
 */

class Docer
{
	private $files;
	
	/**
	 * Initialize a new object.
	 *
	 * @param Array $files name-value pairs where the name is a label used as
	 *   a link and the value is the file, including the path, that will be
	 *   parsed.
	 */
	public function __construct($files = array())
	{
		$this->files = $files;
		$this->version = include dirname(__FILE__) . '/docerVersion.php';
	}
	
	/**
	 * Get a file based on the name as it was set when this object was created.
	 *
	 * @param String $name the file was associated with.
	 *
	 * @return String containing the path to the file if found, FALSE otherwise.
	 */
	public function getFile($name)
	{
		if (!empty($this->files[$name])) {
			return $this->files[$name];
		} else {
			return false;
		}
	}
	
	/**
	 * Render the links to the files passed in the object.
	 *
	 * @return String containing the HTML of the rendered page.
	 */
	public function renderList($baseURL = '')
	{
		if (empty($baseURL)) { $baseURL = 'Docer?i='; }
		$html = '';
		foreach ($this->files as $label => $file) {
			$html .= "<a href=\"$baseURL$label\">$label</a><br />";
		}
		return $html;
	}
	
	/**
	 * Custom sorting function for sorting the method names and have
	 * double underscore methods (ie. "__constructor") placed first.
	 */
	private function customSort($a, $b)
	{
		$out = 0;
		if (substr($a['name'], 0, 2) === '__' && substr($b['name'], 0, 2) === '__') { $out = strcmp($a['name'], $b['name']); }
		elseif (substr($a['name'], 0, 2) === '__') { $out =  -1; }
		elseif (substr($b['name'], 0, 2) === '__') { $out =  1; }
		else { $out = strcmp($a['name'], $b['name']); }
		return $out;
	}
	
	private function customKeySort($a, $b)
	{
		if (strpos($a, 'private') !== false) {
			if (strpos($b, 'private') !== false) {
				if (strpos($a, 'static') !== false) {
					if (strpos($b, 'static') !== false) { return strcmp($a, $b); }
					else { return -1; }
				}
				if (strpos($b, 'static') !== false) {
					return 1;
				}
			}
			else { return -1; }
		}
		if (strpos($b, 'private') !== false) {
			return 1;
		}
		
		if (strpos($a, 'static') !== false) {
			if (strpos($b, 'static') !== false) { return strcmp($a, $b); }
			else { return -1; }
		}
		if (strpos($b, 'static') !== false) {
			return 1;
		}
		
		return strcmp($a, $b);
	}
	
	/**
	 * This will parse out information from the file and return the components
	 * in an array.
	 *
	 * @param String $file is the file to parse. The path to the file must be included.
	 *
	 * @return Array of components.
	 */
	private function parse($file)
	{
		$contents = file_get_contents($file);
		$lines = explode("\n", $contents);
		
		$is_property_section = true; //<-- A flag to designate if we are still in the property section of the class definition, so other variables in methods are not mistaken for defined properties.
		$comment_parts = array('text' => array(''));
		
		$class = array();
		$properties = array();
		$methods = array();
		$inComment = false;
		
		foreach ($lines as $line) {
			$line = trim($line);
			
			if (substr($line, 0, 2) === '/*') {
				// Start comment.
				#$comment_parts = array('text' => array(''));
				$comment_parts = array();
				$part = 'text';
				$part_id = 0;
				$comment_parts[$part][$part_id] = '';
				$inComment = true;
			} elseif (substr($line, 0, 2) === '*/') {
				// End comment.
				foreach ($comment_parts as $part => $data) {
					foreach ($data as $id => $text) { $comment_parts[$part][$id] = trim($text); }
				}
				$inComment = false;
			} elseif ($inComment) {
				// Comment line.
				if (substr($line, 0, 2) === '* ' || $line === '*') {
					$line = substr(trim($line), 2);
				}
				if (substr($line, 0, 1) === '@') {
					$part_text = explode(' ', $line, 2);
					$part = $part_text[0];
					$text = isset($part_text[1]) ? $part_text[1] : '';
					if (empty($comment_parts[$part])) { $comment_parts[$part] = array(); $part_id = 0; } else { $part_id = count($comment_parts[$part]); }
					$comment_parts[$part][$part_id] = $text . PHP_EOL;
				} else {
					$comment_parts[$part][$part_id] .= $line . PHP_EOL;
				}
			} else {
				// Non-comment line.
				if (preg_match('/((?:^|\s+)class)\s+(.+)\s*(?:\{|$)/', $line, $matches)) {
					$parts = explode(' ', $matches[2], 2);
					$class = array('name' => $parts[0], 'designation' => $matches[1], 'modifier' => isset($parts[1]) ? $parts[1] : '', 'comment_parts' => $comment_parts);
					$comment_parts = array('text' => array(''));
					
				} elseif (preg_match('/((?:^|abstract\s+)class)\s+(.+)\s*(?:\{|$)/', $line, $matches)) {
					$parts = explode(' ', $matches[2], 2);
					$class = array('name' => $parts[0], 'designation' => $matches[1], 'modifier' => isset($parts[1]) ? $parts[1] : '', 'comment_parts' => $comment_parts);
					$comment_parts = array('text' => array(''));
					
				#} elseif (preg_match('/((?:^|.+\s+)class)\s+(\w+)\s+extends\s+(\w+)\s*(?:\{|$)/', $line, $matches)) {
				#	$class = array('name' => $matches[2], 'designation' => $matches[1], 'extends' => $matches[3], 'comment_parts' => $comment_parts);
				#	$comment_parts = array('text' => array(''));
					
				} elseif (preg_match('/((?:^|.+\s+)trait)\s+(\w+)\s*(?:\{|$)/', $line, $matches)) {
					$class = array('name' => $matches[2], 'designation' => $matches[1], 'comment_parts' => $comment_parts);
					$comment_parts = array('text' => array(''));
					
				} elseif (preg_match('/((?:^|.+\s+)function)\s+(\w+)\s*(\(.*\))\s*(?:\{|$)/', $line, $matches)) {
					$is_property_section = false;
					if (empty($methods[$matches[1]])) { $methods[$matches[1]] = array(); }
					$methods[$matches[1]][] = array('name' => $matches[2], 'params' => $matches[3] , 'comment_parts' => $comment_parts);
					$comment_parts = array('text' => array(''));
					
				} elseif ($is_property_section && preg_match('/((?:^|.+\s+)const)\s+(\w+)(\s+.*|)\;/', $line, $matches)) {
					if (empty($properties[$matches[1]])) { $properties[$matches[1]] = array(); }
					$properties[$matches[1]][] = array('name' => $matches[2], 'assignment' => $matches[3], 'comment_parts' => $comment_parts);
					$comment_parts = array('text' => array(''));
					
				} elseif ($is_property_section && preg_match('/(?:^|(.+)\s+)(\$\w+)(\s+.*|)\;/', $line, $matches)) {
					if (empty($matches[1])) { $matches[1] = 'public'; } //<-- In case no designation is entered, just straight "$variable;" or "$variable = array();", for example.
					if (empty($properties[$matches[1]])) { $properties[$matches[1]] = array(); }
					$properties[$matches[1]][] = array('name' => $matches[2], 'assignment' => $matches[3], 'comment_parts' => $comment_parts);
					$comment_parts = array('text' => array(''));
				}
			}
		}
		
		foreach ($properties as $designation => &$data) { usort($data, array($this, 'customSort')); }
		uksort($methods, array($this, 'customKeySort'));
		foreach ($methods as $designation => &$data) { usort($data, array($this, 'customSort')); }
		
		return array('class' => $class, 'properties' => $properties, 'methods' => $methods);
	}
	
	/**
	 * Render the documentation page by parsing the file passed.
	 *
	 * @param String $file is the file to parse. The path to the file must be included.
	 *
	 * @return String containing the HTML of the rendered page.
	 */
	public function renderDoc($file, $options = array())
	{
		if (!is_file($file)) { return "ERROR: \"$file\" not found."; }
		$components = $this->parse($file);
		$html = '
			<style type="text/css">
				div.doc{ font-family:courier new; font-size:14px; }
				div.doc div.description{ padding-left:20px; margin-bottom:20px; }
				
				div.doc div.header{ cursor:default; margin-top:8px; font-weight:bold; font-size:0.9em; border:1px solid  #c0c0c0; padding:10px; background-color:#e0e0e0; }
				div.doc div.contents{ font-size:0.8em; border:1px solid #c0c0c0; border-top-width:0px; padding:10px 10px 10px 20px; background-color:#f0f0f0; }
				div.doc div.collapsible_trigger{ cursor:pointer; }
				div.doc div.collapsible_elem{ display:none; }
				div.doc div.section{ font-weight:bold; padding-top:18px; padding-bottom:4px; border-bottom:1px dotted #808080; margin-bottom:10px; width:50%; }
				div.doc div.group + div.doc div.group{ margin-top:10px; }
				
				div.doc span.type{ font-style:italic; color:#808080; }
				div.doc span.params{ font-style:italic; color:#0000aa; }
				div.doc span.variable{ font-weight:bold; color:#0000aa; }
				div.doc dd{ margin-bottom:10px; }
				
				span.designation.public { color:#20920A; }
				span.designation.protected { color:#B99427; }
				span.designation.static { color:#0090B3; }
			</style>
			<script>
				$(document).ready(function() {
					$(".collapsible_trigger").click(function() {
						$(this).next(".collapsible_elem").toggle();
					});
				});
			</script>
		';
		$html .= "<div class=\"doc\">";
		if (!empty($components['class']['modifier'])) {
			$html .= "<h3><span class=\"type\">{$components['class']['designation']}</span> {$components['class']['name']} <span class=\"type\">{$components['class']['modifier']}</span></h3>";
		} else {
			$html .= "<h3><span class=\"type\">{$components['class']['designation']}</span> {$components['class']['name']}</h3>";
		}
		$html .= "<div class=\"description\">" . nl2br(str_replace(array('   ', '  ', "\t"), array('&nbsp; &nbsp;', '&nbsp; ', '&nbsp &nbsp &nbsp;'), $components['class']['comment_parts']['text'][0])). "</div>";
		
		// Properties.
		$count = 0;
		$html .= "<div class=\"header\">Properties</div>";
		$html .= "<div class=\"contents\">";
		foreach ($components['properties'] as $designation => $properties) {
			if (isset($options['hidePrivate']) && $options['hidePrivate'] && substr($designation, 0, 7) === 'private') { continue; }
			if (isset($options['hideProtected']) && $options['hideProtected'] && substr($designation, 0, 9) === 'protected') { continue; }
			if (isset($options['hidePublic']) && $options['hidePublic'] && substr($designation, 0, 6) === 'public') { continue; }
			$html .= "<div class=\"group\">";
			foreach ($properties as $data) {
				$html .= "<span class=\"type\">$designation</span> <span class=\"variable\">$data[name]</span> $data[assignment]<br />";
				$count++;
			}
			$html .= "</div>";
		}
		if ($count == 0) { $html .= "<i>none</i>"; }
		$html .= "</div>";
		
		// Methods.
		foreach ($components['methods'] as $designation => $methods) {
			if (isset($options['hidePrivate']) && $options['hidePrivate'] && substr($designation, 0, 7) === 'private') { continue; }
			if (isset($options['hideProtected']) && $options['hideProtected'] && substr($designation, 0, 9) === 'protected') { continue; }
			if (isset($options['hidePublic']) && $options['hidePublic'] && substr($designation, 0, 6) === 'public') { continue; }
			$designationParts = preg_split('/\s+/', $designation);
			$designation = '';
			foreach ($designationParts as $part) {
				$designation .= "<span class=\"designation $part\">$part</span> ";
			}
			foreach ($methods as $data) {
				$html .= "<div class=\"header collapsible_trigger\"><span class=\"type\">$designation</span> $data[name] <span class=\"params\">$data[params]</span></div>";
				$html .= "<div class=\"contents collapsible_elem\">";
				$html .= nl2br(str_replace(array('   ', '  ', "\t"), array('&nbsp; &nbsp;', '&nbsp; ', '&nbsp &nbsp &nbsp;'), $data['comment_parts']['text'][0]));
				// Get other @ notes besides params and returns.
				$count = 0;
				foreach ($data['comment_parts'] as $type => $strings) {
					if (substr($type, 0, 1) !== '@') { continue; }
					if ($type === '@param' || $type === '@return') { continue; }
					if ($count == 0) { $html .= '<br /><br />'; }
					$count += 1;
					foreach ($strings as $text) {
						$name = $type;
						$type = '';
						$html .= "
							<dt>
								<span class=\"type\">$type</span>
								<span class=\"variable\">$name</span>
							</dt>
							<dd>" . nl2br(str_replace(array('   ', '  '), array('&nbsp; &nbsp;', '&nbsp; '), $text)) . "</dd>
						";
					}
				}
				// @param
				$html .= "<div class=\"section\">Parameters</div>";
				if (empty($data['comment_parts']['@param']) || count($data['comment_parts']['@param']) == 0) {
					$html .= "<i>none</i>";
				} else {
					foreach ($data['comment_parts']['@param'] as $string) {
						#list($type, $name, $text) = explode(' ', $string.'  ', 3);
						$x = preg_split('/\s+/', $string, 3);
						$type = isset($x[0]) ? $x[0] : null;
						$name = isset($x[1]) ? $x[1] : null;
						$text = isset($x[2]) ? $x[2] : null;
						$text = trim($text);
						$html .= "
							<dt>
								<span class=\"type\">$type</span>
								<span class=\"variable\">$name</span>
							</dt>
							<dd>" . nl2br(str_replace(array('   ', '  '), array('&nbsp; &nbsp;', '&nbsp; '), $text)) . "</dd>
						";
					}
				}
				// @return
				$html .= "<div class=\"section\">Return Values</div>";
				if (empty($data['comment_parts']['@return']) || count($data['comment_parts']['@return']) == 0) {
					$html .= "<i>none</i>";
				} else {
					foreach ($data['comment_parts']['@return'] as $string) {
						list($type, $text) = explode(' ', $string.' ', 2);
						$text = trim($text);
						$html .= "<span class=\"type\">$type</span> " . nl2br(str_replace(array('   ', '  '), array('&nbsp; &nbsp;', '&nbsp; '), $text));
					}
				}
				$html .= "</div>";
			}
		}
		$html .= "</div>";
		
		return $html;
	}
}
?>