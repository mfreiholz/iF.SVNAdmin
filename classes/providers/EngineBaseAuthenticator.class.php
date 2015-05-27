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

class EngineBaseAuthenticator implements \svnadmin\core\interfaces\IAuthenticator
{
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IAuthenticator::init()
	 */
	public function init()
	{
		return true;
    }

    /**
     * (non-PHPdoc)
     * @see svnadmin\core\interfaces.IAuthenticator::authenticate()
     */
	public function authenticate($objUser, $password)
	{
		$E = \svnadmin\core\Engine::getInstance();

		// Check for permission of current user.
		// If the user shouldn't have permission, we do not need to use the
		// authentication function.
		if (!$E->getAclManager()->hasPermission($objUser, \ACL_MOD_BASIC, \ACL_ACTION_LOGIN))
		{
			return false;
		}

		// Correct user/pass combination?
		if (!$E->getUserViewProvider()->authenticate($objUser, $password))
		{
			return false;
		}

		return true;
	}
}
?>