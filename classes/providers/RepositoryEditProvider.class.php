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
   * Object to handle operations of the "svnlook" executable.
   * @var IF_SVNLookC
   * SVN svnlook export svn access path list file
   */
  private $_svnLook = NULL;

  /**
   * Holds the singelton instance of this class.
   * @var svnadmin\providers\RepositoryEditProvider
   */
  private static $_instance = NULL;

  /**
   * Holds multiple repository configurations.
   * e.g.: array(
   *      0 => array(
   *        'SVNParentPath' => '/var/svn/repositories',
   *        ...
   *      ),
   *      1 => array(
   *        'SVNParentPath' => '/var/svn/repository-archive-2011',
   *        ...
   *      ),
   *      2 => array(
   *        'SVNParentPath' => '/var/svn/repository-archive-2012',
   *        ...
   *      ),
   *    )
   * @var array
   */
  private $_config = array();

  /**
   * Initializes the object by Engine configuration.
   */
  public function __construct()
  {
    // core engine
    $engine = \svnadmin\core\Engine::getInstance();
    // read the config "data/config.ini"
    $config = $engine->getConfig();

    // Subversion class for browsing.
    // get the client exe file svn, such as: /usr/bin/svn
    $this->_svnClient = new \IF_SVNClientC($engine->getConfig()
      ->getValue('Repositories:svnclient', 'SvnExecutable'));

    // Subversion class for administration.
    // get the server exe svnadmin，such as: /usr/bin/svnadmin
    $this->_svnAdmin = new \IF_SVNAdminC($engine->getConfig()
      ->getValue('Repositories:svnclient', 'SvnAdminExecutable'));

    // Subversion class for administration.
    // get the server exe svnlook，such as: /usr/bin/svnlook
    $this->_svnLook = new \IF_SVNLookC($engine->getConfig()
      ->getValue('Repositories:svnclient', 'SvnLookExecutable'));

    // Load default repository location configuration.
    // get the svn repositories root path，such as: /home/svn/svnrepos
    $defaultSvnParentPath = $engine->getConfig()
      ->getValue('Repositories:svnclient', 'SVNParentPath');

    // Set as default.
    $this->_config[0]['SVNParentPath'] = $defaultSvnParentPath;
    $this->_config[0]['description'] = 'Repositories';

    // Issue #5: Support multiple path values for SVNParentPath
    // Try to load more repository locations.
    $index = (int)1;
    while (true) {
      $svnParentPath = $config->getValue('Repositories:svnclient:' . $index, 'SVNParentPath');
      if ($svnParentPath != null) {
        $this->_config[$index]['SVNParentPath'] = $svnParentPath;
      } else {
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
    // save() do nothing.
    if_log_debug('save() function do nothing.');
    return true;
  }

  /**
   * create repository
   * @see svnadmin\core\interfaces.IRepositoryEditProvider::create()
   */
  public function create(\svnadmin\core\entities\Repository $oRepository, $type = "fsfs", $reason = null)
  {
    // create SVN repository folder in the server svn root path
    if_log_debug('create SVN repository folder in the server svn root path');

    // get the root path
    $svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');

    if (!file_exists($svnParentPath)) {
      throw new \Exception("The repository parent path doesn't exists: " .
        $svnParentPath);
    }

    // Build the absolute path of the current repository
    $path = $svnParentPath . '/' . $oRepository->name;
    $this->_svnAdmin->create($path, $type, $reason);

    return true;
  }

  /**
   * delete repository folder
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
   * make svn sub folder
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
   * use svnadmin dump to download the .dump backup file
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
    header('Content-Disposition: attachment; filename=' . $oRepository->name . date('_Y-m-d_His') . '.dump');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Stream file to STDOUT now.
    return $this->_svnAdmin->dump($absoluteRepositoryPath);
  }

  /**
   * export repository file tree to file
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
    global $appEngine;
    $svnBaseURL = $appEngine->getConfig()->getValue('Subversion', 'BaseURL');
    if (!endsWith($svnBaseURL, '/')) {
      $svnBaseURL = $svnBaseURL . '/';
    }

    $absoluteRepositoryPath = $svnParentPath . '/' . $oRepository->name;

    $svnRepoURL = $svnBaseURL . 'svn/' . $oRepository->name . '/';

    // Set HTTP header
    header('Content-Description: Repository Tree');
    header('Content-type: application/octet-stream');  // Tell the browser that this is a file in file stream format
    header('Content-Disposition: attachment; filename=' . $oRepository->name . date('_Y-m-d_His') . '.csv');   // set the filename
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Stream file to STDOUT now.
    return $this->_svnLook->tree($absoluteRepositoryPath, $svnRepoURL);
  }


  /**
   * Download the access path permission csv file of the respository.
   * @param \svnadmin\core\entities\Repository $oRepository
   * @param $accessPathList
   * @return bool
   * @throws \IF_SVNException
   */
  public function downloadAccessPath(\svnadmin\core\entities\Repository $oRepository, $accessPathList)
  {
    $svnParentPath = $this->getRepositoryConfigValue($oRepository, 'SVNParentPath');

    if ($svnParentPath == NULL) {
      throw new \Exception('Invalid parent-identifier: ' .
        $oRepository->getParentIdentifier());
    }

    $absoluteRepositoryPath = $svnParentPath . '/' . $oRepository->name;
    if_log_array($absoluteRepositoryPath, '$absoluteRepositoryPath');


    // Set HTTP header
    header('Content-Description: Repository Access Path');
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $oRepository->name . date('_Y-m-d_His') . '.csv');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Stream file to STDOUT now.
    return $this->_svnAdmin->downloadAccessPath($oRepository->name, $accessPathList);
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
    } else if (isset($this->_config[$oRepository->parentIdentifier])) {
      $v = $this->_config[$oRepository->parentIdentifier][$key];
    }

    return $v;
  }
}

?>