<?php

defined('_INIT') or die;

abstract class Folder
{
	/**
	 * Copy a folder.
	 *
	 * @param   string   $src          The path to the source folder.
	 * @param   string   $dest         The path to the destination folder.
	 * @param   string   $path         An optional base path to prefix to the file names.
	 * @param   string   $force        Force copy.
	 * @param   boolean  $use_streams  Optionally force folder/file overwrites.
	 *
	 * @return  boolean  True on success.
	 */
	public static function copy($src, $dest, $path = '', $force = false, $use_streams = false)
	{
		@set_time_limit(ini_get('max_execution_time'));

		$FTPOptions = JClientHelper::getCredentials('ftp');

		if ($path)
		{
			$src = JPath::clean($path . '/' . $src);
			$dest = JPath::clean($path . '/' . $dest);
		}

		// Eliminate trailing directory separators, if any
		$src = rtrim($src, DIRECTORY_SEPARATOR);
		$dest = rtrim($dest, DIRECTORY_SEPARATOR);

		if (!self::exists($src))
		{
			throw new RuntimeException('Source folder not found', -1);
		}
		if (self::exists($dest) && !$force)
		{
			throw new RuntimeException('Destination folder not found', -1);
		}

		// Make sure the destination exists
		if (!self::create($dest))
		{
			throw new RuntimeException('Cannot create destination folder', -1);
		}

		// If we're using ftp and don't have streams enabled
		if ($FTPOptions['enabled'] == 1 && !$use_streams)
		{
			// Connect the FTP client
			$ftp = JClientFtp::getInstance($FTPOptions['host'], $FTPOptions['port'], array(), $FTPOptions['user'], $FTPOptions['pass']);

			if (!($dh = @opendir($src)))
			{
				throw new RuntimeException('Cannot open source folder', -1);
			}
			// Walk through the directory copying files and recursing into folders.
			while (($file = readdir($dh)) !== false)
			{
				$sfid = $src . '/' . $file;
				$dfid = $dest . '/' . $file;
				switch (filetype($sfid))
				{
					case 'dir':
						if ($file != '.' && $file != '..')
						{
							$ret = self::copy($sfid, $dfid, null, $force);
							if ($ret !== true)
							{
								return $ret;
							}
						}
						break;

					case 'file':
						// Translate path for the FTP account
						$dfid = JPath::clean(str_replace(JPATH_ROOT, $FTPOptions['root'], $dfid), '/');
						if (!$ftp->store($sfid, $dfid))
						{
							throw new RuntimeException('Copy file failed', -1);
						}
						break;
				}
			}
		}
		else
		{
			if (!($dh = @opendir($src)))
			{
				throw new RuntimeException('Cannot open source folder', -1);
			}
			// Walk through the directory copying files and recursing into folders.
			while (($file = readdir($dh)) !== false)
			{
				$sfid = $src . '/' . $file;
				$dfid = $dest . '/' . $file;
				switch (filetype($sfid))
				{
					case 'dir':
						if ($file != '.' && $file != '..')
						{
							$ret = self::copy($sfid, $dfid, null, $force, $use_streams);
							if ($ret !== true)
							{
								return $ret;
							}
						}
						break;

					case 'file':
						if ($use_streams)
						{
							$stream = JFactory::getStream();
							if (!$stream->copy($sfid, $dfid))
							{
								throw new RuntimeException('Cannot copy file: ' . $stream->getError(), -1);
							}
						}
						else
						{
							if (!@copy($sfid, $dfid))
							{
								throw new RuntimeException('Copy file failed', -1);
							}
						}
						break;
				}
			}
		}
		return true;
	}

	/**
	 * Create a folder -- and all necessary parent folders.
	 *
	 * @param   string   $path  A path to create from the base path.
	 * @param   integer  $mode  Directory permissions to set for folders created. 0755 by default.
	 *
	 * @return  boolean  True if successful.
	 */
	public static function create($path, $mode = 0755)
	{
		return mkdir($path, $mode, true);
	}

	/**
	 * Delete a folder.
	 *
	 * @param   string  $path  The path to the folder to delete.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public static function delete($path)
	{
		return rmdir($path);
	}

	/**
	 * Moves a folder.
	 *
	 * @param   string   $src          The path to the source folder.
	 * @param   string   $dest         The path to the destination folder.
	 * @param   string   $path         An optional base path to prefix to the file names.
	 * @param   boolean  $use_streams  Optionally use streams.
	 *
	 * @return  mixed  Error message on false or boolean true on success.
	 *
	 * @since   11.1
	 */
	public static function move($src, $dest, $path = '', $use_streams = false)
	{
		$FTPOptions = JClientHelper::getCredentials('ftp');

		if ($path)
		{
			$src = JPath::clean($path . '/' . $src);
			$dest = JPath::clean($path . '/' . $dest);
		}

		if (!self::exists($src))
		{
			return JText::_('JLIB_FILESYSTEM_ERROR_FIND_SOURCE_FOLDER');
		}
		if (self::exists($dest))
		{
			return JText::_('JLIB_FILESYSTEM_ERROR_FOLDER_EXISTS');
		}
		if ($use_streams)
		{
			$stream = JFactory::getStream();
			if (!$stream->move($src, $dest))
			{
				return JText::sprintf('JLIB_FILESYSTEM_ERROR_FOLDER_RENAME', $stream->getError());
			}
			$ret = true;
		}
		else
		{
			if ($FTPOptions['enabled'] == 1)
			{
				// Connect the FTP client
				$ftp = JClientFtp::getInstance($FTPOptions['host'], $FTPOptions['port'], array(), $FTPOptions['user'], $FTPOptions['pass']);

				// Translate path for the FTP account
				$src = JPath::clean(str_replace(JPATH_ROOT, $FTPOptions['root'], $src), '/');
				$dest = JPath::clean(str_replace(JPATH_ROOT, $FTPOptions['root'], $dest), '/');

				// Use FTP rename to simulate move
				if (!$ftp->rename($src, $dest))
				{
					return JText::_('Rename failed');
				}
				$ret = true;
			}
			else
			{
				if (!@rename($src, $dest))
				{
					return JText::_('Rename failed');
				}
				$ret = true;
			}
		}
		return $ret;
	}

	/**
	 * Wrapper for the standard file_exists function
	 *
	 * @param   string  $path  Folder name relative to installation dir
	 *
	 * @return  boolean  True if path is a folder
	 *
	 * @since   11.1
	 */
	public static function exists($path)
	{
		return is_dir(Path::clean($path));
	}

	/**
	 * Utility function to read the files in a folder.
	 *
	 * @param   string   $path           The path of the folder to read.
	 * @param   string   $filter         A filter for file names.
	 * @param   mixed    $recurse        True to recursively search into sub-folders, or an integer to specify the maximum depth.
	 * @param   boolean  $full           True to return the full path to the file.
	 * @param   array    $exclude        Array with names of files which should not be shown in the result.
	 * @param   array    $excludefilter  Array of filter to exclude
	 *
	 * @return  array  Files in the given folder.
	 *
	 * @since   11.1
	 */
	public static function files($path, $filter = '.', $recurse = false, $full = false, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'),
		$excludefilter = array('^\..*', '.*~'))
	{
		// Check to make sure the path valid and clean
		$path = Path::clean($path);

		// Is the path a folder?
		if (!is_dir($path))
		{
			//JLog::add(JText::sprintf('JLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER_FILES', $path), JLog::WARNING, 'jerror');
			throw new Error( __('The path given is not a folder: "{1}"', $path), null );
			return false;
		}

		// Compute the excludefilter string
		if (count($excludefilter))
		{
			$excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
		}
		else
		{
			$excludefilter_string = '';
		}

		// Get the files
		$arr = self::_items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, true);

		// Sort the files
		asort($arr);
		return array_values($arr);
	}

	/**
	 * Utility function to read the folders in a folder.
	 *
	 * @param   string   $path           The path of the folder to read.
	 * @param   string   $filter         A filter for folder names.
	 * @param   mixed    $recurse        True to recursively search into sub-folders, or an integer to specify the maximum depth.
	 * @param   boolean  $full           True to return the full path to the folders.
	 * @param   array    $exclude        Array with names of folders which should not be shown in the result.
	 * @param   array    $excludefilter  Array with regular expressions matching folders which should not be shown in the result.
	 *
	 * @return  array  Folders in the given folder.
	 *
	 * @since   11.1
	 */
	public static function folders($path, $filter = '.', $recurse = false, $full = false, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'),
		$excludefilter = array('^\..*'))
	{
		// Check to make sure the path valid and clean
		$path = Path::clean($path);

		// Is the path a folder?
		if (!is_dir($path))
		{
			//JLog::add(JText::sprintf('JLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER_FOLDER', $path), JLog::WARNING, 'jerror');
			throw new Error( __('The path given is not a folder: "{1}"', $path), null );
			return false;
		}

		// Compute the excludefilter string
		if (count($excludefilter))
		{
			$excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
		}
		else
		{
			$excludefilter_string = '';
		}

		// Get the folders
		$arr = self::_items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, false);

		// Sort the folders
		asort($arr);
		return array_values($arr);
	}

	/**
	 * Function to read the files/folders in a folder.
	 *
	 * @param   string   $path                  The path of the folder to read.
	 * @param   string   $filter                A filter for file names.
	 * @param   mixed    $recurse               True to recursively search into sub-folders, or an integer to specify the maximum depth.
	 * @param   boolean  $full                  True to return the full path to the file.
	 * @param   array    $exclude               Array with names of files which should not be shown in the result.
	 * @param   string   $excludefilter_string  Regexp of files to exclude
	 * @param   boolean  $findfiles             True to read the files, false to read the folders
	 *
	 * @return  array  Files.
	 *
	 * @since   11.1
	 */
	protected static function _items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles)
	{
		@set_time_limit(ini_get('max_execution_time'));

		$arr = array();

		// Read the source directory
		if (!($handle = @opendir($path)))
		{
			return $arr;
		}

		while (($file = readdir($handle)) !== false)
		{
			if ($file != '.' && $file != '..' && !in_array($file, $exclude)
				&& (empty($excludefilter_string) || !preg_match($excludefilter_string, $file)))
			{
				// Compute the fullpath
				$fullpath = $path . '/' . $file;

				// Compute the isDir flag
				$isDir = is_dir($fullpath);

				if (($isDir xor $findfiles) && preg_match("/$filter/", $file))
				{
					// (fullpath is dir and folders are searched or fullpath is not dir and files are searched) and file matches the filter
					if ($full)
					{
						// Full path is requested
						$arr[] = $fullpath;
					}
					else
					{
						// Filename is requested
						$arr[] = $file;
					}
				}
				if ($isDir && $recurse)
				{
					// Search recursively
					if (is_int($recurse))
					{
						// Until depth 0 is reached
						$arr = array_merge($arr, self::_items($fullpath, $filter, $recurse - 1, $full, $exclude, $excludefilter_string, $findfiles));
					}
					else
					{
						$arr = array_merge($arr, self::_items($fullpath, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles));
					}
				}
			}
		}
		closedir($handle);
		return $arr;
	}

	/**
	 * Lists folder in format suitable for tree display.
	 *
	 * @param   string   $path      The path of the folder to read.
	 * @param   string   $filter    A filter for folder names.
	 * @param   integer  $maxLevel  The maximum number of levels to recursively read, defaults to three.
	 * @param   integer  $level     The current level, optional.
	 * @param   integer  $parent    Unique identifier of the parent folder, if any.
	 *
	 * @return  array  Folders in the given folder.
	 *
	 * @since   11.1
	 */
	public static function listFolderTree($path, $filter, $maxLevel = 3, $level = 0, $parent = 0)
	{
		$dirs = array();
		if ($level == 0)
		{
			$GLOBALS['_JFolder_folder_tree_index'] = 0;
		}
		if ($level < $maxLevel)
		{
			$folders = self::folders($path, $filter);

			// First path, index foldernames
			foreach ($folders as $name)
			{
				$id = ++$GLOBALS['_JFolder_folder_tree_index'];
				$fullName = Path::clean($path . '/' . $name);
				$dirs[] = array('id' => $id, 'parent' => $parent, 'name' => $name, 'fullname' => $fullName,
					'relname' => str_replace(JPATH_ROOT, '', $fullName));
				$dirs2 = self::listFolderTree($fullName, $filter, $maxLevel, $level + 1, $id);
				$dirs = array_merge($dirs, $dirs2);
			}
		}
		return $dirs;
	}

	/**
	 * Makes path name safe to use.
	 *
	 * @param   string  $path  The full path to sanitise.
	 *
	 * @return  string  The sanitised string.
	 *
	 * @since   11.1
	 */
	public static function makeSafe($path)
	{
		$regex = array('#[^A-Za-z0-9:_\\\/-]#');
		return preg_replace($regex, '', $path);
	}
}
