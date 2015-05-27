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
	 * Object to handle operations of the "svn" executable.
	 * @var IF_SVNClientC
	 */
    private $_svnClient = NULL;

    /**
     * Object to handle operations of the "svnadmin" executable.
     * @var IF_SVNAdminC
     */
	private $_svnAdmin = NULL;

	/**
	 * Holds the singelton instance of this class.
	 * @var svnadmin\providers\RepositoryEditProvider
	 */
	private static $_instance = NULL;
	
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
		
		// Subversion class for administration.
		$this->_svnAdmin = new \IF_SVNAdminC($engine->getConfig()
				->getValue('Repositories:svnclient', 'SvnAdminExecutable'));
		
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
	 * @return svnadmin\providers\RepositoryEditProvider
	 */
	public static function getInstance()
	{
		if (self::$_instance == NULL) {
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
	public function create(\svnadmin\core\entities\Repository $oRepository, $type = "fsfs")
	{
		$svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');
		
		if (!file_exists($svnParentPath)) {
			throw new \Exception("The repository parent path doesn't exists: " .
					$svnParentPath);
		}

		$path = $svnParentPath . '/' . $oRepository->name;
		$this->_svnAdmin->create($path, $type);

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::delete()
	 */
	public function delete(\svnadmin\core\entities\Repository $oRepository)
	{
		$svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');
		
		if ($svnParentPath == NULL) {
			throw new \Exception('Invalid parent-identifier: ' .
					$oRepository->getParentIdentifier());
		}
		
		$path = $svnParentPath . '/' . $oRepository->name;
		$this->_svnAdmin->delete($path);
		
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::mkdir()
	 */
	public function mkdir(\svnadmin\core\entities\Repository $oRepository, array $paths)
	{
		$svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');
		
		if ($svnParentPath == NULL) {
			throw new \Exception('Invalid parent-identifier: ' .
					$oRepository->getParentIdentifier());
		}
		
		// Create absolute paths.
		for ($i = 0; $i < count($paths); ++$i) {
			$paths[$i] = $svnParentPath . '/' . $oRepository->name . '/' . $paths[$i];
		}
		
		$this->_svnClient->svn_mkdir($paths);
		
    	return true;
    }
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::dump()
	 */
	public function dump(\svnadmin\core\entities\Repository $oRepository)
	{
		$svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');
		
		if ($svnParentPath == NULL) {
			throw new \Exception('Invalid parent-identifier: ' .
					$oRepository->getParentIdentifier());
		}
		
		$absoluteRepositoryPath = $svnParentPath . '/' . $oRepository->name;
		
		// Set HTTP header
		header('Content-Description: Repository Dump');
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $oRepository->name . '.dump');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		
		// Stream file to STDOUT now.
		return $this->_svnAdmin->dump($absoluteRepositoryPath);
	}	
	
	/**
	 * Gets the configuration value associated to the given Repository object
	 * (identified by 'parentIdentifier')
	 * 
	 * @param \svnadmin\core\entities\Repository $oRepository
	 * @param string $key
	 * @return string
	 */
	protected function getRepositoryConfigValue(\svnadmin\core\entities\Repository $oRepository, $key = 'SVNParentPath')
	{
		$v = null;

		if ($oRepository->parentIdentifier === null) {
			$v = $this->_config[0][$key];
		}
		else if (isset($this->_config[$oRepository->parentIdentifier])){
			$v = $this->_config[$oRepository->parentIdentifier][$key];
		}
		
		return $v;
	}
}
?>