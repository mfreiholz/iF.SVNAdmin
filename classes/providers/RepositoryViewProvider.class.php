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
	private static $_instance = NULL;

	/**
	 * The svn-client class object to handle command on the repository.
	 * @var \IF_SVNClientC
	 */
	private $_svnClient = NULL;
	
	/**
	 * Holds multiple repository configurations.
	 * e.g.: array(
	 *			0 => array(
	 *				'SVNParentPath' => '/var/svn/repositories',
	 *				...
	 *			),
	 *			1 => array(
	 *				'SVNParentPath' => '/var/svn/repository-archive-2011',
	 *				...
	 *			),
	 *			2 => array(
	 *				'SVNParentPath' => '/var/svn/repository-archive-2012',
	 *				...
	 *			),
	 *		)
	 * @var array
	 */
	private $_config = array();


	/**
	 * Initializes the object by Engine configuration.
	 */
	public function __construct()
	{
		$engine = \svnadmin\core\Engine::getInstance();
		$config = $engine->getConfig();
		
		// Subversion class for browsing.
		$this->_svnClient = new \IF_SVNClientC($engine->getConfig()
				->getValue('Repositories:svnclient', 'SvnExecutable'));
		
		// Load default repository location configuration.
		$defaultSvnParentPath = $engine->getConfig()
				->getValue('Repositories:svnclient', 'SVNParentPath');
		
		// Set as default.
		$this->_config[0]['SVNParentPath'] = $defaultSvnParentPath;
		$this->_config[0]['description'] = 'Repositories';
		
		// Issue #5: Support multiple path values for SVNParentPath
		// Try to load more repository locations.
		$index = (int) 1;
		while (true) {
			$svnParentPath = $config->getValue('Repositories:svnclient:' . $index, 'SVNParentPath');
			if ($svnParentPath != null) {
				$this->_config[$index]['SVNParentPath'] = $svnParentPath;
			}
			else {
				break;
			}
			
			$description = $config->getValue('Repositories:svnclient:' . $index, 'Description');
			if ($description != null) {
				$this->_config[$index]['description'] = $description;
			}
			
			++$index;
		}
	}

	/**
	 * Gets the singelton instance of this object.
	 *
	 * @return \svnadmin\providers\RepositoryViewProvider
	 */
	public static function getInstance()
	{
		if (self::$_instance == null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IProvider::init()
	 */
	public function init()
	{
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
	 * @see svnadmin\core\interfaces.IRepositoryViewProvider::getRepositoryParents()
	 */
	public function getRepositoryParents()
	{
		$ret = array();
		
		foreach ($this->_config as $parentIdentifier => $options) {
			$ret[] = new \svnadmin\core\entities\RepositoryParent(
					$parentIdentifier,
					$this->getRepositoryParentConfigValue($parentIdentifier, 'SVNParentPath'),
					$this->getRepositoryParentConfigValue($parentIdentifier, 'description')
				);
		}
		
		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryViewProvider::getRepositories()
	 */
	public function getRepositories()
	{
		$ret = array();
		
		foreach ($this->_config as $parentIdentifier => $options) {
			$list = $this->_svnClient->listRepositories($options['SVNParentPath']);
			
			foreach ($list as $name) {
				$ret[] = new \svnadmin\core\entities\Repository($name, $parentIdentifier);
			}
		}
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryViewProvider::getRepositoriesOfParent()
	 */
	public function getRepositoriesOfParent(\svnadmin\core\entities\RepositoryParent $parent)
	{
		$ret = array();
		
		$svnParentPath = $this->getRepositoryParentConfigValue($parent->identifier);
		if ($svnParentPath != NULL) {
			$list = $this->_svnClient->listRepositories($svnParentPath);
			
			foreach ($list as $name) {
				$ret[] = new \svnadmin\core\entities\Repository($name, $parent->identifier);
			}
		}
		
		return $ret;
	}

    /**
     * (non-PHPdoc)
     * @see svnadmin\core\interfaces.IRepositoryViewProvider::listPath()
     */
	public function listPath(\svnadmin\core\entities\Repository $oRepository, $relativePath)
    {
		// Get SVNParentPath of given Repository object.
		$svnParentPath = $this->getRepositoryParentConfigValue(
				$oRepository->getParentIdentifier(), 'SVNParentPath');
		
		// Absolute path to the repository.
		$repo = $svnParentPath . '/' . $oRepository->name;

		if ($relativePath == '/') {
			$relativePath = '';
		}

		// Append the relative path.
		$uri = $repo . '/' . $relativePath;

		$ret = array();

		// Get the file list.
		// @throws Exception
		$svn_entry_list = $this->_svnClient->svn_list($uri);

		if (empty($svn_entry_list->entries)) {
			return $ret;
		}

		foreach ($svn_entry_list->entries as $entry) {
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
	
	/**
	 * Gets the configuration value associated to the given $parentIdentifier.
	 * 
	 * @param string $parentIdentifier
	 * @param string $key
	 * @return string
	 */
	protected function getRepositoryParentConfigValue($parentIdentifier, $key = 'SVNParentPath')
	{
		$v = null;

		if ($parentIdentifier === null) {
			$v = $this->_config[0][$key];
		}
		else if (isset($this->_config[$parentIdentifier])){
			if (isset ($this->_config[$parentIdentifier][$key])) {
				$v = $this->_config[$parentIdentifier][$key];
			}
		}
		
		return $v;
	}
}
?>