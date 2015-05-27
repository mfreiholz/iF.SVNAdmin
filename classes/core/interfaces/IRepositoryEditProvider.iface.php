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

interface IRepositoryEditProvider extends IEditProvider
{
	/**
	 * Creates a new repository.
	 *
	 * @param \svnadmin\core\entities\Repository   $oRepository
	 * @param string $type The repository type (fsfs or bdb)
	 *
	 * @return bool
	 */
	public function create(\svnadmin\core\entities\Repository $oRepository, $type);

	/**
	 * Deletes an existing repository.
	 *
	 * @param \svnadmin\core\entities\Repository $oRepository
	 *
	 * @return bool
	 */
	public function delete(\svnadmin\core\entities\Repository $oRepository);

	/**
	 * Creates a new folder in the repository (including parents of folder).
	 *
	 * @param \svnadmin\core\entities\Repository $oRepository
	 * @param array $path Paths to the folders which should be created.
	 *
	 * @return bool
	 */
	public function mkdir(\svnadmin\core\entities\Repository $oRepository, array $paths);
	
	/**
	 * Dumps a repository file system content to STDOUT (Browser).
	 * 
	 * @param \svnadmin\core\entities\Repository $oRepository
	 * 
	 * @return bool
	 */
	public function dump(\svnadmin\core\entities\Repository $oRepository);
}
?>