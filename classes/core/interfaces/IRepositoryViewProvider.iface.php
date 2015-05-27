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

interface IRepositoryViewProvider extends IViewProvider
{
	/**
	 * Gets all configured repository parents (SVNParentPath).
	 * 
	 * @return array<svnadmin\core\entities\RepositoryParent>
	 */
	public function getRepositoryParents();
	
	/**
	 * Gets all existing repositories.
	 *
	 * @return array<\svnadmin\core\entities\Repository>
	 */
	public function getRepositories();
	
	/**
	 * Getes all existing repositories of  a specific location.
	 * 
	 * @return array<\svnadmin\core\entities\Repository>
	 */
	public function getRepositoriesOfParent(\svnadmin\core\entities\RepositoryParent $parent);

	/**
	 * Gets the contents of the given repository path.
	 *
	 * @param \svnadmin\core\entities\Repository $oRepository
	 * @param string $relativePath The relative path inside the given repository.
	 * @return array<\svnadmin\core\entities\RepositoryPath>
	 */
	public function listPath(\svnadmin\core\entities\Repository $oRepository, $relativePath);
}
?>