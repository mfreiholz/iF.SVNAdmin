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
  interface IPathsViewProvider extends IViewProvider
  {
    public function getPaths();

    public function getPathsOfGroup( $objGroup );
    public function getPathsOfUser( $objUser );
    public function getPathsOfRepository($objRepository);

    public function getUsersOfPath( $objAccessPath );
    public function getGroupsOfPath( $objAccessPath );
  }
}
?>