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
namespace svnadmin\core\interfaces;

interface IPathsEditProvider extends IEditProvider
{
	// @Move to IProvider
	public function reset();

	public function createAccessPath($objAccessPath);
	public function deleteAccessPath($objAccessPath);

	public function assignGroupToAccessPath($objGroup, $objAccessPath, $objPermission);
	public function removeGroupFromAccessPath($objGroup, $objAccessPath);
	public function removeGroupFromAllAccessPaths($objGroup);

	public function assignUserToAccessPath($objUser, $objAccessPath, $objPermission);
	public function removeUserFromAccessPath($objUser, $objAccessPath);
	public function removeUserFromAllAccessPaths($objUser);
}
?>