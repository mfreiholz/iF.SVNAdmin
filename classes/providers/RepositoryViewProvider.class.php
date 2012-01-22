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

class RepositoryViewProvider implements \svnadmin\core\interfaces\IRepositoryViewProvider
{
	/**
	 * The singelton instance of this class.
	 * @var \svnadmin\providers\RepositoryViewProvider
	 */
	private static $m_instance = NULL;

	/**
	 * @var bool
	 */
	private $m_init_done = false;

	/**
	 * The directory where all the repositories takes place.
	 * @var string
	 */
	public $svnParentPath = NULL;

	/**
	 * The svn-client class object to handle command on the repository.
	 * @var \IF_SVNClientC
	 */
	private $m_svnclient = NULL;


	/**
	 * The constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Gets the singelton instance of this object.
	 *
	 * @return \svnadmin\providers\RepositoryViewProvider
	 */
	public static function getInstance()
	{
		if( self::$m_instance == NULL )
		{
			self::$m_instance = new RepositoryViewProvider();
		}
		return self::$m_instance;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IProvider::init()
	 */
	public function init()
	{
		if(!$this->m_init_done)
		{
			global $appEngine;
			$this->m_init_done = true;
			$this->svnParentPath = $appEngine->getConfig()->getValue("Repositories:svnclient", "SVNParentPath");
			$this->m_svnclient = new \IF_SVNClientC($appEngine->getConfig()->getValue("Repositories:svnclient", "SvnExecutable"));
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IViewProvider::isUpdateable()
	 */
	public function isUpdateable()
	{
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IViewProvider::update()
	 */
	public function update()
	{
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryViewProvider::getRepositories()
	 */
	public function getRepositories()
	{
		$ret = array();

		$repoList = $this->m_svnclient->listRepositories($this->svnParentPath);
		foreach ($repoList as $repoName)
		{
			$ret[] = new \svnadmin\core\entities\Repository($repoName);
		}

		return $ret;
	}

    /**
     * (non-PHPdoc)
     * @see svnadmin\core\interfaces.IRepositoryViewProvider::listPath()
     */
	public function listPath(\svnadmin\core\entities\Repository $oRepository, $relativePath)
    {
		// Absolute path to the repository.
		$repo = $this->svnParentPath;
		$repo.= "/";
		$repo.= $oRepository->name;

		if ($relativePath == "/")
		{
			$relativePath = "";
		}

		// Append the relative path.
		$uri = $repo."/".$relativePath;

		$ret = array();

		// Get the file list.
		// @throws Exception
		$svn_entry_list = $this->m_svnclient->svn_list($uri);

		if (empty($svn_entry_list->entries))
		{
			return $ret;
		}

		foreach ($svn_entry_list->entries as $entry)
		{
			$oRP = new \svnadmin\core\entities\RepositoryPath();
			$oRP->parent = $relativePath;
			$oRP->name = $entry->name;
			$oRP->type = ($entry->isdir ? 0 : 1);
			$oRP->author = $entry->author;
			$oRP->revision = $entry->rev;
			$oRP->date = $entry->date;
			$ret[] = $oRP;
		}

		return $ret;
	}
}
?>