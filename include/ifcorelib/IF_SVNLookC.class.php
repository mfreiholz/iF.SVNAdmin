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
 * Provides functionality of the "svnlook.exe" executable by using the
 * executable and parsing the output.
 *
 * @author Manuel Freiholz, insaneFactory
 */
class IF_SVNLookC extends IF_SVNBaseC
{
	/**
	 * Path to the "svnlook.exe" binary.
	 * @var string
	 */
	private $m_svnlook = NULL;


	/**
	 * Constructor.
	 *
	 * @param string $svn_admin_binary Absolute path to "svnlook" executable.
	 * @throws IF_SVNException
	 */
	public function __construct($svn_admin_binary)
  {
    parent::__construct();
    $this->m_svnlook = $svn_admin_binary;


  }

  /** output the repository path list
   * @param $path
   * @param null $file
   * @return bool
   * @throws IF_SVNException
   */
  public function tree($path, $svnRepoURL)
  {
    if (empty($path)) {
      throw new IF_SVNException('Empty path parameter for tree() command.');
    }

    $cmd = $this->m_svnlook . ' tree --full-paths ' . $path;
    # do not use passthru($cmd) here,
    # use passthru($cmd) can not add index and base url to path.
    $all_path = shell_exec($cmd);
    $path_array = explode("\n", $all_path);
    $return_string = tr('Index,SVN Web Link') . "\n";
    foreach ( $path_array as $index => $item) {
      if ($item == '/') {
        $return_string = $return_string . ($index + 1) . ',' . $svnRepoURL . "\n";
      }
      else{
        $return_string = $return_string . ($index + 1) . ',' . $svnRepoURL . $item . "\n";
      }
    }
    echo $return_string;
    return true;
  }
}