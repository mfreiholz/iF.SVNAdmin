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

class RepositoryPath
{
	public $parent;
	public $name;
	public $type;
	public $author;
	public $revision;
	public $date;

	public function __construct(
		$parent=null, $name=null, $type=null, $author=null,
		$revision=null, $date=null)
	{
		$this->parent = $parent;
		$this->name = $name;
		$this->type = $type;
		$this->author = $author;
		$this->revision = $revision;
		$this->date = $date;
	}

	public function getParent()
	{
		return $this->parent;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getAuthor()
	{
		return $this->author;
	}

	public function getRevision()
	{
		return $this->revision;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function getRelativePath()
	{
		if (empty($this->parent) || $this->parent == "/")
			return $this->name;
		else
			return $this->parent . "/" . $this->name;
	}

	public function getEncodedRelativePath()
	{
		$relPath = self::getRelativePath();
		return rawurlencode( $relPath );
	}
}
?>