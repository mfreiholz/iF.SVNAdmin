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
     * SVN客户端执行程序svn
	 */
    private $_svnClient = NULL;

    /**
     * Object to handle operations of the "svnadmin" executable.
     * @var IF_SVNAdminC
     * SVN服务端执行程序svnadmin
     */
	private $_svnAdmin = NULL;



  /**
   * Object to handle operations of the "svnlook" executable.
   * @var IF_SVNLookC
   * SVN服务端执行程序svnadmin
   */
  private $_svnLook = NULL;

	/**
	 * Holds the singelton instance of this class.
	 * @var svnadmin\providers\RepositoryEditProvider
     * 保存此类的单例实例
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
     * 多仓库配置列表
	 */
	private $_config = array();
	
	/**
	 * Initializes the object by Engine configuration.
     * 通过引擎配置初始化对象
	 */
	public function __construct()
	{
		// 核心引擎
	    $engine = \svnadmin\core\Engine::getInstance();
		// 读取配置文件"data/config.ini"
		$config = $engine->getConfig();
		
		// Subversion class for browsing.
        // 获取SVN客户端程序svn,如/usr/bin/svn
		$this->_svnClient = new \IF_SVNClientC($engine->getConfig()
				->getValue('Repositories:svnclient', 'SvnExecutable'));
		
		// Subversion class for administration.
        // 获取SVN服务端程序svnadmin，如/usr/bin/svnadmin
		$this->_svnAdmin = new \IF_SVNAdminC($engine->getConfig()
				->getValue('Repositories:svnclient', 'SvnAdminExecutable'));

    // Subversion class for administration.
    // 获取SVN服务端程序svnlook，如/usr/bin/svnlook
    $this->_svnLook = new \IF_SVNLookC($engine->getConfig()
      ->getValue('Repositories:svnclient', 'SvnLookExecutable'));
		
		// Load default repository location configuration.
        // 获取默认的仓库根路径，如/home/svn/svnrepos
		$defaultSvnParentPath = $engine->getConfig()
				->getValue('Repositories:svnclient', 'SVNParentPath');

		// Set as default.
        // 默认设置
		$this->_config[0]['SVNParentPath'] = $defaultSvnParentPath;
		$this->_config[0]['description'] = 'Repositories';
		
		// Issue #5: Support multiple path values for SVNParentPath
		// Try to load more repository locations.
        // 支持SVN父路径存在多个多路径值
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
     * 获取此类的单例实例
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
     * 实现init()接口方法
     */
	public function init()
	{
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IEditProvider::save()
     * 实现save()接口方法
	 */
	public function save()
	{
        //save()函数仅返回true,并未做实质性的事情
        if_log_debug('save() function do nothing.');
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::create()
     * 实现create()接口方法
	 */
	public function create(\svnadmin\core\entities\Repository $oRepository, $type = "fsfs")
	{
        // 在服务器SVN根目录下面创建仓库文件夹
        if_log_debug('create SVN repository folder in the server svn root path');

	    // 获取SVN根目录
		$svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');
		
		if (!file_exists($svnParentPath)) {
			throw new \Exception("The repository parent path doesn't exists: " .
					$svnParentPath);
		}

		// 构建当前仓库的绝对路径
		$path = $svnParentPath . '/' . $oRepository->name;
		$this->_svnAdmin->create($path, $type);

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IRepositoryEditProvider::delete()
     * 实现delete()接口方法，删除仓库目录
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
     * 实现mkdir()接口方法，创建目录
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
     * 实现dump()接口方法，备份仓库
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

  /** 导出SVN仓库的目录树结构
   * @param \svnadmin\core\entities\Repository $oRepository
   * @return bool
   * @throws \IF_SVNException
   */
  public function tree(\svnadmin\core\entities\Repository $oRepository)
  {
    $svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');

    if ($svnParentPath == NULL) {
      throw new \Exception('Invalid parent-identifier: ' .
        $oRepository->getParentIdentifier());
    }

    $absoluteRepositoryPath = $svnParentPath . '/' . $oRepository->name;

    // Set HTTP header
    header('Content-Description: Repository Tree');
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $oRepository->name . '.xls');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Stream file to STDOUT now.
    return $this->_svnLook->tree($absoluteRepositoryPath);
  }

  /**
	 * Gets the configuration value associated to the given Repository object
	 * (identified by 'parentIdentifier')
     * 获取仓库的配置信息
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