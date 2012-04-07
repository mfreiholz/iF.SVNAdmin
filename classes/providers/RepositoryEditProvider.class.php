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
namespace svnadmin\providers;

class RepositoryEditProvider implements \svnadmin\core\interfaces\IRepositoryEditProvider
{
	/**
	 * Indicates whether the init() method has been called.
	 * @var bool
	 */
	private $m_init_done = false;

	/**
	 * The directory where all the repositories takes place,
	 * also known as "SVNParentPath" in Apache configuration.
	 * @var string
	 */
	public $svnParentPath = NULL;

	/**
	 * Object to handle operations of the "svn" executable.
	 * @var IF_SVNClientC
	 */
    private $m_svn = NULL;

    /**
     * Object to handle operations of the "svnadmin" executable.
     * @var IF_SVNAdminC
     */
	private $m_svnadmin = NULL;

	/**
	 * Holds the singelton instance of this class.
	 * @var svnadmin\providers\RepositoryEditProvider
	 */
	private static $m_instance = NULL;

	/**
	 * Gets the singelton instance of this object.
	 *
	 * @return svnadmin\providers\RepositoryEditProvider
	 */
	public static function getInstance()
	{
		if( self::$m_instance == NULL )
		{
			self::$m_instance = new RepositoryEditProvider();
		}
		return self::$m_instance;
    }

    /**
     * (non-PHPdoc)
     * @see svnadmin\core\interfaces.IProvider::init()
     */
	public function init()
	{
		if (!$this->m_init_done)
		{
			global $appEngine;
			$this->m_init_done = true;
			$this->svnParentPath = $appEngine->getConfig()->getValue("Repositories:svnclient", "SVNParentPath");
			$this->m_svn = new \IF_SVNClientC($appEngine->getConfig()->getValue("Repositories:svnclient", "SvnExecutable"));
			$this->m_svnadmin = new \IF_SVNAdminC($appEngine->getConfig()->getValue("Repositories:svnclient", "SvnAdminExecutable"));
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IEditProvider::save()
	 */
	public function save()
	{
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::create()
	 */
	public function create(\svnadmin\core\entities\Repository $oRepository, $type = "fsfs" )
	{
		if (!file_exists($this->svnParentPath))
		{
			throw new \Exception("The repository parent path doesn't exists: " . $this->svnParentPath);
		}

		$path = $this->svnParentPath."/".$oRepository->name;
		$this->m_svnadmin->create($path, $type);

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::delete()
	 */
	public function delete(\svnadmin\core\entities\Repository $oRepository)
	{
		$path = $this->svnParentPath."/".$oRepository->name;
		$this->m_svnadmin->delete($path);
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::mkdir()
	 */
	public function mkdir(\svnadmin\core\entities\Repository $oRepository, array $paths)
	{
		// Create absolute paths.
		for ($i = 0; $i < count($paths); ++$i) {
			$paths[$i] = $this->svnParentPath . '/' . $oRepository->name . '/' . $paths[$i];
		}
		
		$this->m_svn->svn_mkdir($paths);
    	return true;
    }
}
?>