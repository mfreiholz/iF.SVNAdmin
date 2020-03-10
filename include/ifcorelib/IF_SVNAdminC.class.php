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
		// 	throwcreate --fs-type new IF_SVNException('Permission denied! Can not execute "svnadmin" executable: '.$this->m_svnadmin);
	}

	/**
	 * Creates a new empty repository.
	 * 此处是创建SVN仓库的核心代码
	 * @param string $path Absolute path to the new repository
	 * @param string $type Repository type: fsfs=file system(default); bdb=berkley db (not recommended)
	 * @throws IF_SVNException
	 * @throws IF_SVNCommandExecutionException
	 */
	public function create($path, $type="fsfs")
	{
	    // 检查仓库的绝对路径是否存在
		if (empty($path))
		{
			throw new IF_SVNException('Empty path parameter for create() command.');
		}

		// Validate repository name.
        // 验证仓库名称，需要为英文、数字下划线或破折号，点号
		$pattern = '/^([a-z0-9\_\-.]+)$/i';
		$repo_name = basename($path);

		if (!preg_match($pattern, $repo_name))
		{
			throw new IF_SVNException('Invalid repository name: '.$repo_name.' (Allowed pattern: '.$pattern.')');
		}

		// 设置svnadmin create需要使用的参数序列
		$args = array();
		if (!empty($this->config_directory))
		{
			$args["--config-dir"] = escapeshellarg($this->config_directory);
		}

		if (!empty($type))
		{
			$args["--fs-type"] = escapeshellarg($type);
		}

		// 调用基类中的创建svn命令字符串，组装成最终需要使用的命令行字符串
		$cmd = self::create_svn_command($this->m_svnadmin, "create", self::encode_local_path($path), $args, false);

		// 设置输出和退出码，并执行命令
		$output = null;
		$exitCode = 0;
		exec($cmd, $output, $exitCode);

		// 如果退出码非零，则抛出异常
		if ($exitCode != 0)
		{
			throw new IF_SVNCommandExecutionException('Command='.$cmd.'; Return='.$exitCode.'; Output='.$output.';');
		}
	}

	/**
	 * Deletes the repository at the given path.
	 * 删除给定路径的仓库
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
	 * 备份仓库
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

  public function downloadAccessPath($repository_name, $accessPathList)
  {
    global $appEngine;
    $return_string = tr('Index,SVN Path,Project Manager,User,Group') . "\n";
    if (empty($accessPathList)) {
      echo $repository_name . tr(": no access about this repository");
      return true;
    }
    else{
      // 检查每个访问路径的权限信息
      foreach ($accessPathList as $index => $accessPathObj) {
        if_log_array($accessPathObj, 'Access Path item');
        // 获取访问路径对应SVN WEB的URL地址
        $accessPathURL = $accessPathObj->getURLPath();
        if_log_array($accessPathURL, '$accessPathURL');

        // View data.
        // Project Managers data
        $managers = $appEngine->getAclManager()->getUsersOfAccessPath($accessPathObj->path);
        if_log_array($managers,'$managers');
        $accessPathObj->managers = $managers;
        $managers_string = str_replace(',', "\n", $accessPathObj->getManagersAsString());
        $return_string = $return_string . ($index + 1) . ',' . $accessPathURL . ',';
        $return_string = $return_string . '"' . $managers_string . '",';

        // Users data
        $users = $appEngine->getAccessPathViewProvider()->getUsersOfPath($accessPathObj);
        if_log_array($users,'$users');
        $user_string = '';
        if (empty($users)){
          $user_string = tr('no user');
        }
        foreach ($users as $user){
          $username = $user->getName();
          $permission = tr($user->getPermission());
          $user_string = $user_string . $username . ':' . $permission . "\n\n";

        }
        $return_string = $return_string . '"' . $user_string . '"';

        // Groups data
        $groups = $appEngine->getAccessPathViewProvider()->getGroupsOfPath($accessPathObj);
        if_log_array($groups,'$groups');
        $group_string = '';
        foreach ($groups as $group){
          $groupname = $group->getName();
          $permission = tr($group->getPermission());
          // get group members
          // do not conside the subgroup of the group
          $members_array = $group->getUsersOfGroup();
          $members_string = '';
          if (empty($members_array)){
            $members_string = tr('no member');
          }
          foreach ($members_array as $index=>$user){
            $username = $user->getName();
            if ($index != count($members_array) - 1) {
              $members_string = $members_string . $username . ", ";
            }
            else{
              $members_string = $members_string . $username;
            }
          }
          $group_string = $group_string . $groupname . ':' . $permission . "\n" . tr('Group Members:') . $members_string. "\n\n";

        }
        $return_string = $return_string . ',"' . $group_string . '"';



//        $return_string = $return_string . implode("," , '$users') . ',';
//        $return_string = $return_string . implode("," , '$groups') . "\n";
        $return_string = $return_string . "\n";
      }
    }
    echo $return_string;
    return true;

  }


}