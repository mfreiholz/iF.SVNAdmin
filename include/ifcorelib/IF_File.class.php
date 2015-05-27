<?php
/**
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */

/**
 * Represents a file or directory of the filesystem.
 * All operations are are handled on the given path in constructor.
 * If a absolute IF_File object is needed, call the toAbsolute() method
 * or initialize the object with an absolute path.
 *
 * @author Manuel Freiholz, insaneFactory.com
 */
class IF_File
{
	/**
	 * Holds the path which the user used with constructor.
	 *
	 * @var string
	 */
	private $m_path = null;

	/**
	 * Constructor.
	 *
	 * @param string $path
	 * @param bool $bConvertAbsolute Indicates whether the given $path parameter
	 * 								 should be converted to an absolute path.
	 */
	public function __construct($path, $bConvertAbsolute=false)
	{
		$this->m_path = $path;

		if ($bConvertAbsolute)
		{
			$this->m_path = realpath($path);
		}
	}

	/**
	 * Creates a new IF_File object from internal data,
	 * but with absolute path informations.
	 *
	 * Note: The file have to exist!
	 *
	 * @return IF_File
	 */
	public function toAbsolute()
	{
		return new IF_File(self::getAbsolutePath());
	}

	/**
	 * Gets the path which the user used for the constructor.
	 *
	 * @return String
	 */
	public function getPath()
	{
		return $this->m_path;
	}

	/**
	 * Gets the absolute path of this file.
	 *
	 * Note: The file have to exist!
	 *
	 * @return String
	 */
	public function getAbsolutePath()
	{
		return realpath($this->m_path);
	}

	/**
	 * Gets the path to this file.
	 *
	 * e.g.: "/test/path/myfile.txt" would return "/test/path"
	 */
	public function getParentPath()
	{
		return pathinfo($this->m_path, PATHINFO_DIRNAME);
	}

	/**
	 * Gets the file-name part of this file (including the extension).
	 *
	 * @return String
	 */
	public function getBaseName()
	{
		return basename($this->m_path);
	}

	/**
	 * Get the file-name part of this file (excluding the extension).
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return pathinfo($this->m_path, PATHINFO_FILENAME);
	}

	/**
	 * Gets the extension of the file.
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return pathinfo($this->m_path, PATHINFO_EXTENSION);
	}

	/**
	 * Gets to know whether this IF_File object points to a folder.
	 *
	 * @return bool
	 */
	public function isDirectory()
	{
		if (is_dir($this->m_path))
		{
			return true;
		}
		return false;
	}

	/**
	 * Gets to know whether this IF_File object points to a regular file.
	 *
	 * @return bool
	 */
	public function isFile()
	{
		if (is_file($this->m_path))
		{
			return true;
		}
		return false;
	}

	/**
	 * Gets to know whether this IF_File object points to a symbolic link.
	 *
	 * @return bool
	 */
	public function isLink()
	{
		if (is_link($this->m_path))
		{
			return true;
		}
		return false;
	}

	/**
	 * Gets the length, in bytes, of a file and returns it.
	 *
	 * @return int
	 */
	public function length()
	{
		return filesize($this->m_path);
	}

	/**
	 * Gets the owner ID of this file.
	 *
	 * @return int
	 */
	public function getOwner()
	{
		return fileowner($this->m_path);
	}

	/**
	 * Gets the group ID of this file.
	 *
	 * @return int
	 */
	public function getGroup()
	{
		return filegroup($this->m_path);
	}

	/**
	 * Gets the timestamp of the last access to this file.
	 *
	 * @return int
	 */
	public function getLastAccess()
	{
		return fileatime($this->m_path);
	}

	/**
	 * Gets the timestamp of the last modification to this file.
	 *
	 * @return int
	 */
	public function getLastModified()
	{
		return filemtime($this->m_path);
	}

	/**
	 * Gets the permission for this file in octal representation.
	 * Examples (seperated by comma): 0664, 0777, 0774
	 *
	 * @return int
	 */
	public function getPermissions()
	{
		return fileperms($this->m_path);
	}

	/**
	 * Deletes this file or directory.
	 * Note that if this object points to a directory, the directory
	 * must be empty, otherwise a error occurs.
	 *
	 * @return bool
	 */
	public function delete()
	{
		if ($this->isDirectory())
		{
			return rmdir($this->m_path);
		}
		else
		{
			return unlink($this->m_path);
		}
	}

	/**
	 * Checks whether this file exists.
	 *
	 * @return bool
	 */
	public function exists()
	{
		if (file_exists($this->m_path))
		{
			return true;
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @param IF_File $oDestFile
	 */
	public function copyTo(IF_File $file)
	{
		return copy($this->m_path, $file->getPath());
	}
}
?>