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
include("include/config.inc.php");

//
// Authentication
//

$engine = \svnadmin\core\Engine::getInstance();

if (!$engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)) {
	$engine->forwardInvalidModule(true);
}

// 检查当前用户权限
$engine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_ADD);
// 加载翻译文件
$appTR->loadModule("repositorycreate");

//
// Actions
//

// 判断'create'这个变量是否设置，也就是是否配置有'create'提交按钮
if (check_request_var('create'))
{
    // 处理动作
    if_log_debug('处理创建仓库动作');
	$engine->handleAction('create_repository');
}

//
// View Data
//

// 将仓库父路径的值渲染到HTML模板中
SetValue('RepositoryParentList', $engine->getRepositoryViewProvider()->getRepositoryParents());
// 渲染模板
ProcessTemplate("repository/repositorycreate.html.php");
?>