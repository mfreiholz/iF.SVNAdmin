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
namespace svnadmin\core\interfaces
{
  interface IUserViewProvider extends IViewProvider
  {
    /**
     * Gets an array filled with user objects.
     * @return User[]
     */
    public function getUsers();

    /**
     * Checks whether the given user exists.
     * @param $objUser
     * @return bool
     */
    public function userExists( $objUser );
    
    /**
     * Note: Maybe! this is a workarround.. im just not sure what is the best way to handle the authentication...
     *
     * Authenticates the user against his password.
     * @return bool
     */
    public function authenticate( $objUser, $password );
  }
}
?>