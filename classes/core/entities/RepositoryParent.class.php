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
namespace svnadmin\core\entities;

/**
 * Description of RepositoryParent
 *
 * @author Manuel Freiholz
 */
class RepositoryParent
{
	/**
	 * The unique identifier for this repository-parent location.
	 * @var string
	 */
	public $identifier = NULL;

	/**
	 * The path to this repository-parent location.
	 * @var string
	 */
	public $path = NULL;
	
	/**
	 * Description for the repository-parent location.
	 * @var string
	 */
	public $description = NULL;
	
	
	public function __construct($identifier = NULL, $path = NULL,
			$description = NULL)
	{
		$this->identifier = $identifier;
		$this->path = $path;
		$this->description = $description;
	}
	
	public function getEncodedIdentifier()
	{
		return rawurlencode($this->identifier);
	}
	
	public function getEncodedPath()
	{
		return rawurlencode($this->path);
	}
}