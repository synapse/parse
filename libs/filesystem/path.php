<?php

defined('_INIT') or die;

class Path
{
	/**
	 * Checks if a path's permissions can be changed.
	 * @param   string  $path  Path to check.
	 * @return  boolean  True if path can have mode changed.
	 */
	public static function canChmod($path)
	{
		$perms = fileperms($path);
		if ($perms !== false)
		{
			if (@chmod($path, $perms ^ 0001))
			{
				@chmod($path, $perms);
				return true;
			}
		}

		return false;
	}

	/**
	 * Chmods files and directories recursively to given permissions.
	 * @param   string  $path        Root path to begin changing mode [without trailing slash].
	 * @param   string  $filemode    Octal representation of the value to change file mode to [null = no change].
	 * @param   string  $foldermode  Octal representation of the value to change folder mode to [null = no change].
	 * @return  boolean  True if successful [one fail means the whole operation failed].
	 */
	public static function setPermissions($path, $filemode = '0644', $foldermode = '0755')
	{
		// Initialise return value
		$ret = true;

		if (is_dir($path))
		{
			$dh = opendir($path);

			while ($file = readdir($dh))
			{
				if ($file != '.' && $file != '..')
				{
					$fullpath = $path . '/' . $file;
					if (is_dir($fullpath))
					{
						if (!self::setPermissions($fullpath, $filemode, $foldermode))
						{
							$ret = false;
						}
					}
					else
					{
						if (isset($filemode))
						{
							if (!@ chmod($fullpath, octdec($filemode)))
							{
								$ret = false;
							}
						}
					}
				}
			}
			closedir($dh);
			if (isset($foldermode))
			{
				if (!@ chmod($path, octdec($foldermode)))
				{
					$ret = false;
				}
			}
		}
		else
		{
			if (isset($filemode))
			{
				$ret = @ chmod($path, octdec($filemode));
			}
		}

		return $ret;
	}

	/**
	 * Get the permissions of the file/folder at a give path.
	 * @param   string  $path  The path of a file/folder.
	 * @return  string  Filesystem permissions.
	 */
	public static function getPermissions($path)
	{
		$path = self::clean($path);
		$mode = @ decoct(@ fileperms($path) & 0777);

		if (strlen($mode) < 3)
		{
			return '---------';
		}

		$parsed_mode = '';
		for ($i = 0; $i < 3; $i++)
		{
			// Read
			$parsed_mode .= ($mode{$i} & 04) ? "r" : "-";

			// Write
			$parsed_mode .= ($mode{$i} & 02) ? "w" : "-";

			// Execute
			$parsed_mode .= ($mode{$i} & 01) ? "x" : "-";
		}

		return $parsed_mode;
	}

	/**
	 * Checks for snooping outside of the file system root.
	 * @param   string  $path  A file system path to check.
	 * @param   string  $ds    Directory separator (optional).
	 * @return  string  A cleaned version of the path or exit on error.
	 */
	public static function check($path, $ds = DIRECTORY_SEPARATOR)
	{
		if (strpos($path, '..') !== false)
		{
			// Don't translate
			throw new Exception('Path::check Use of relative paths not permitted', 20);
		}

		$path = self::clean($path);
		if ((ROOT_PATH != '') && strpos($path, self::clean(ROOT_PATH)) !== 0)
		{
			// Don't translate
			throw new Exception('Path::check Snooping out of bounds @ ' . $path, 20);
		}

		return $path;
	}

	/**
	 * Function to strip additional / or \ in a path name.
	 * @param   string  $path  The path to clean.
	 * @param   string  $ds    Directory separator (optional).
	 * @return  string  The cleaned path.
	 */
	public static function clean($path, $ds = DIRECTORY_SEPARATOR)
	{
		$path = trim($path);

		if (empty($path))
		{
			$path = ROOT_PATH;
		}
		// Remove double slashes and backslashes and convert all slashes and backslashes to DIRECTORY_SEPARATOR
		// If dealing with a UNC path don't forget to prepend the path with a backslash.
		elseif (($ds == '\\') && ($path[0] == '\\' ) && ( $path[1] == '\\' ))
		{
			$path = "\\" . preg_replace('#[/\\\\]+#', $ds, $path);
		}
		else
		{
			$path = preg_replace('#[/\\\\]+#', $ds, $path);
		}

		return $path;
	}

	/**
	 * Method to determine if script owns the path.
	 * @param   string  $path  Path to check ownership.
	 * @return  boolean  True if the php script owns the path passed.
	 */
    /*
	public static function isOwner($path)
	{
		$tmp = md5(mt_rand());
		$ssp = ini_get('session.save_path');
		$jtp = JPATH_SITE . '/tmp';

		// Try to find a writable directory
		$dir = is_writable('/tmp') ? '/tmp' : false;
		$dir = (!$dir && is_writable($ssp)) ? $ssp : false;
		$dir = (!$dir && is_writable($jtp)) ? $jtp : false;

		if ($dir)
		{
			$test = $dir . '/' . $tmp;

			// Create the test file
			$blank = '';
			JFile::write($test, $blank, false);

			// Test ownership
			$return = (fileowner($test) == fileowner($path));

			// Delete the test file
			JFile::delete($test);

			return $return;
		}

		return false;
	}
    */

	/**
	 * Searches the directory paths for a given file.
	 * @param   mixed   $paths  An path string or array of path strings to search in
	 * @param   string  $file   The file name to look for.
	 * @return  mixed   The full path and file name for the target file, or boolean false if the file is not found in any of the paths.
	 */
	public static function find($paths, $file)
	{
		// Force to array
		if (!is_array($paths) && !($paths instanceof Iterator))
		{
			settype($paths, 'array');
		}

		// Start looping through the path set
		foreach ($paths as $path)
		{
			// Get the path to the file
			$fullname = $path . '/' . $file;

			// Is the path based on a stream?
			if (strpos($path, '://') === false)
			{
				// Not a stream, so do a realpath() to avoid directory
				// traversal attempts on the local file system.

				// Needed for substr() later
				$path = realpath($path);
				$fullname = realpath($fullname);
			}

			/*
			 * The substr() check added to make sure that the realpath()
			 * results in a directory registered so that
			 * non-registered directories are not accessible via directory
			 * traversal attempts.
			 */
			if (file_exists($fullname) && substr($fullname, 0, strlen($path)) == $path)
			{
				return $fullname;
			}
		}

		// Could not find the file in the set of paths
		return false;
	}
}
