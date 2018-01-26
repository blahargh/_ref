<?php
namespace IMP;

/**
 * Walk through every file in a folder recursively, drilling down the directory tree.
 *
 * Property information:
 *   'filename'       -> The name of the file.
 *   'file'           -> The filename including its full path.
 *   'isDir'          -> If TRUE, then file is a directory.
 *   'basePath'       -> The root directory where the journey started.
 *   'relativePath'   -> The current working directory in the journey.
 *   'relativeFile'   -> The filename including the relative path based off of the root directory. This is cwd + filename.
 *   'subdirectories' -> Array of subdirectories found.
 */
class FileWalker
{
	private $root = '';
	private $cwd = '';
	private $dh = null;
	private $dirs = array();
	private $dirIndex = 0;
	private $rootObject = null;
	private $skipDirectories = array();
	private $deleteOnWalk = false;
	private $recursiveWalk = true;
	private $returnDirectories = false;

	public $filename = '';             // The name of the file.
	public $file = '';                 // The filename including its full path.
	public $isDir = false;             // If TRUE, then file is a directory.
	public $basePath = '';             // The root directory where the journey started.
	public $relativePath = '';         // The current working directory in the journey.
	public $relativeFile = '';         // The filename including the relative path based off of the root directory. This is cwd + filename.
	public $subdirectories = array();  // Array of subdirectories found.

	/**
	 * Set the root object for subdirectories so that settings done in the main object can be
	 * seen in subdirectory objects.
	 *
	 * @param object $obj - The FileWalker object for the root directory.
	 */
	private function setRootObject($obj)
	{
		$this->rootObject = $obj;
	}

	/**
	 * Instantiate an object.
	 *
	 * @param string $root - The root directory to start in.
	 * @param string $cwd  - (optional) The current working directory as it traverses the directory tree.
	 */
	public function __construct($root, $cwd = '')
	{
		if (is_dir($root)) {
			$this->root = ($root !== '' && substr($root, -1) !== '/') ? $root . '/' : $root;
			$this->cwd = ($cwd !== '' && substr($cwd, -1) !== '/') ? $cwd . '/' : $cwd;
			if (!is_dir($this->root . $this->cwd)) { return false; }
			$this->rootObject = $this;
			$this->basePath = $this->root;
			$this->relativePath = $this->cwd;
		} else {
			return false;
		}
	}
	/**
	 * Retrieve information regarding the next file encountered.
	 *
	 * @param bool $flag_skipDirectory - Propagate the "skipDirectory" flag to mark the correct iteration.
	 *
	 * @return array - Returns TRUE if a file was found, FALSE if no more files found.
	 * Sets some properties in the main object to contain current file's information:
	 *   'filename'       -> The name of the file.
	 *   'file'           -> The filename including its full path.
	 *   'isDir'          -> If TRUE, then file is a directory.
	 *   'basePath'       -> The root directory where the journey started.
	 *   'relativePath'   -> The current working directory in the journey.
	 *   'relativeFile'   -> The filename including the relative path based off of the root directory. This is cwd + filename.
	 *   'subdirectories' -> Array of subdirectories found.
	 */
	public function walk()
	{
		if (empty($this->dh)) {
			// No opened directory. Open the root directory.
			$this->path = $this->root . $this->cwd;
			if (empty($this->path)) { return false; } #<-- $root + $cwd is probably not a valid directory, maybe it no longer exists?
			if (!($this->dh = opendir($this->path))) {
				throw new \Exception('Unable to open directory, "' . $this->path . '"');
			}
		}

		if (in_array($this->cwd, $this->rootObject->skipDirectories)) {
			// Skip cwd.
			$file = false;
			$this->dirs = array();
		} else {
			// Get the next file.
			$file = readdir($this->dh);
		}

		if ($file === false) {
			// No more files in the directory.
			// Check if any subdirectories where found. If so, go through the file in each one, unless $recursiveWalk is set to FALSE.
			if ($this->recursiveWalk && !empty($this->dirs[$this->dirIndex])) {
				$file = $this->dirs[$this->dirIndex]->walk();
				if ($file) {
					// File found in the subdirectory or subsequent sub-subdirectories.
					return $file;
				} else {
					// Subdirectory done.
					if ($this->rootObject->deleteOnWalk) { rmdir($this->dirs[$this->dirIndex]->basePath . $this->dirs[$this->dirIndex]->relativePath); }
					$this->dirs[$this->dirIndex] = null;
					$this->dirIndex++;
					// Check next subdirectory.
					return $this->walk();
				}
			} else {
				// Completely done.
				closedir($this->dh);
				$this->dh = null;
				$this->file = '';
				$this->filename = '';
			}
		} else {
			// File found.
			// Ignore certain files.
			if ($file === '.') { return $this->walk(); }
			if ($file === '..') { return $this->walk(); }
			// If a directory, then set it up for traversal later, after the files.
			if (is_dir($this->path . $file)) {
				$obj = new self($this->root, $this->cwd . $file);
				$obj->setRootObject($this->rootObject);
				$this->dirs[] = $obj;
				$this->rootObject->subdirectories[] = $this->path . $file;
				if ($this->returnDirectories) {
					$this->filename = $file;
					$this->file = $this->path . $file;
					$this->isDir = true;
				} else {
					// Check next file.
					return $this->walk();
				}
			} else {
				// Valid file found.
				$this->filename = $file;
				$this->file = $this->path . $file;
				$this->isDir = false;
				if ($this->rootObject->deleteOnWalk) { unlink($this->file); }
			}
		}

		if ($this->file) {
			$this->rootObject->filename = $this->filename;
			$this->rootObject->file = $this->file;
			$this->rootObject->basePath = $this->root;
			$this->rootObject->relativePath = $this->cwd;
			$this->rootObject->relativeFile = $this->cwd . $this->filename;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Ignore a current directory from further traversal.
	 *
	 * @param string $cwd - (optional) The current relative path to ignore. If not passed in, then the current relativePath found.
	 */
	public function skipDirectory($cwd = null)
	{
		if ($cwd === null) { $cwd = $this->relativePath; }
		$this->skipDirectories[] = $cwd;
	}

	/**
	 * Set to either walk recursively or not. Defaults to TRUE.
	 */
	public function setRecursiveWalk($bool)
	{
		$this->recursiveWalk = $bool ? true : false;
	}

	/**
	 * Set whether to return directories when walking. Defaults to FALSE.
	 */
	public function setReturnDirectories($bool)
	{
		$this->returnDirectories = $bool ? true : false;
	}

	/**
	 * Delete all files and directories.
	 */
	public function deleteAll()
	{
		$this->deleteOnWalk = true;
		while($this->walk()) { }
		$this->deleteOnWalk = false;
	}
}
?>
