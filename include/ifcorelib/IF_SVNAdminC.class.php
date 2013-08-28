<?php
/**
 * iF.SVNAdmin
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
 * Provides functionality of the "svnadmin.exe" executable by using the
 * executable and parsing the output.
 *
 * @author Manuel Freiholz, insaneFactory
 */
class IF_SVNAdminC extends IF_SVNBaseC
{
	/**
	 * Path to the "svnadmin.exe" binary.
	 * @var string
	 */
	private $m_svnadmin = NULL;


	/**
	 * Constructor.
	 *
	 * @param string $svn_admin_binary Absolute path to "svnadmin" executable.
	 * @throws IF_SVNException
	 */
	public function __construct($svn_admin_binary)
	{
		parent::__construct();
		$this->trust_server_cert = false;
		$this->non_interactive = false;
		$this->m_svnadmin = $svn_admin_binary;

		// if (!file_exists($svn_admin_binary))
		// 	throw new IF_SVNException('Path to "svnadmin" binary does not exist: '.$this->m_svnadmin);
		// if (!is_executable($svn_admin_binary))
		// 	throw new IF_SVNException('Permission denied! Can not execute "svnadmin" executable: '.$this->m_svnadmin);
	}

	/**
	 * Creates a new empty repository.
	 *
	 * @param string $path Absolute path to the new repository
	 * @param string $type Repository type: fsfs=file system(default); bdb=berkley db (not recommended)
	 * @throws IF_SVNException
	 * @throws IF_SVNCommandExecutionException
	 */
	public function create($path, $type="fsfs")
	{
		if (empty($path))
		{
			throw new IF_SVNException('Empty path parameter for create() command.');
		}

		// Validate repository name.
		$pattern = '/^([a-z0-9\_\-]+)$/i';
		$repo_name = basename($path);

		if (!preg_match($pattern, $repo_name))
		{
			throw new IF_SVNException('Invalid repository name: '.$repo_name.' (Allowed pattern: '.$pattern.')');
		}

		$args = array();
		if (!empty($this->config_directory))
		{
			$args["--config-dir"] = escapeshellarg($this->config_directory);
		}

		if (!empty($type))
		{
			$args["--fs-type"] = escapeshellarg($type);
		}

		$cmd = self::create_svn_command($this->m_svnadmin, "create", self::encode_local_path($path), $args, false);

		$output = null;
		$exitCode = 0;
		exec($cmd, $output, $exitCode);

		if ($exitCode != 0)
		{
			throw new IF_SVNCommandExecutionException('Command='.$cmd.'; Return='.$exitCode.'; Output='.$output.';');
		}
	}

	/**
	 * Deletes the repository at the given path.
	 *
	 * @param string $path Path to the repository.
	 * @return bool
	 */
	public function delete($path)
	{
		$files = glob($path."/*"/*, GLOB_MARK*/); // GLOB_MARK = Adds a ending slash to directory paths.
		foreach( $files as $f )
		{
			if (is_dir($f))
			{
				self::delete( $f );
			}
			else
			{
				chmod($f, 0777);
				unlink($f);
			}
		}

		if (is_dir($path))
		{
			rmdir($path);
		}

		return true;
	}
	
	/**
	 * Dump the contents of the given file-system
	 * 
	 * @param string $path	Local path to the repository.
	 * @param string $file [optional]	If NULL the binary output of the dump
	 *									comannd is directed to STDOUT (browser).
	 *									Otherwise... not implemented.
	 */
	public function dump($path, $file = NULL)
	{
		if (empty($path)) {
			throw new IF_SVNException('Empty path parameter for dump() command.');
		}
		
		$args = array();
		
		if (!empty($this->config_directory)) {
			$args['--config-dir'] = escapeshellarg($this->config_directory);
		}
		
		if ($file != NULL) {
			$args[] = '> ' . escapeshellarg($file);
		}
		
		$cmd = self::create_svn_command($this->m_svnadmin, 'dump', self::encode_local_path($path), $args, false);
		
		if ($file != NULL) {
			// Not supported....
		}
		else {
			passthru($cmd);
		}
		return true;
	}
}