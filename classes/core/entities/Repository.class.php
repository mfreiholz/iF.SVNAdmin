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

class Repository
{
	/**
	 * The name of the repository.
	 * @var string
	 */
	public $name;

    /**
     * The description of the repository.
     * @var string
     */
    public $repoDescription;
	
	/**
	 * The parent identifier of the repository.
	 * (Association to an SVNParentPath)
	 * @var string/int
	 */
	public $parentIdentifier;

	/**
	 * Constructor.
	 * 
	 * @param string $name
	 * @param string $parentIdentifier 
	 * @param string $repoDescription
	 */
	public function __construct($name = null, $parentIdentifier = null, $repoDescription = null)
	{
		$this->name = $name;
		$this->parentIdentifier = $parentIdentifier;
		$this->repoDescription = $repoDescription;
	}

	public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->repoDescription;
    }


    public function getParentIdentifier()
	{
		return $this->parentIdentifier;
	}

	public function getEncodedName()
	{
		return rawurlencode($this->name);
	}
	
	public function getEncodedParentIdentifier()
	{
		return rawurlencode($this->parentIdentifier);
	}

  public function getURLPath()
  {
    global $appEngine;
    $baseURL = $appEngine->getConfig()->getValue('Subversion', 'BaseURL');
    if (! endsWith($baseURL, '/'))
    {
      $baseURL = $baseURL . '/';
    }
    $basePath = $baseURL . 'svn/' . str_replace(':', '', $this->name) . '/';
    return $basePath;
  }

	public static function compare( $o1, $o2 )
	{
		if ($o1->name == $o2->name) {
			return 0;
		}
		return ($o1->name > $o2->name) ? +1 : -1;
	}


}
?>