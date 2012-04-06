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
namespace svnadmin\providers\ldap;

/**
 * The  CachedLdapUserViewProvider class provides fast access for data which
 * comes from the LdapUserViewProvider. It only accesses the LDAP server inside
 * the "update()" method implementation.
 * 
 * @author Manuel Freiholz, insaneFactory.com
 */
class CachedLdapUserViewProvider
	extends \svnadmin\providers\ldap\LdapUserViewProvider
{
	/**
	 * Cache file for users.
	 * @var \IF_JsonObjectStorage
	 */
	private $_cache;
	
	/**
	 * Holds the singleton instance of this class.
	 * @var \svnadmin\providers\ldap\CachedLdapUserViewProvider 
	 */
	private static $_instance;
	
	/**
	 * Indicates whether the
	 * @var type 
	 */
	private $_update_done = false;
	
	/**
	 * Indicates whether the 'init()' method has been called.
	 * @var type 
	 */
	private $_init_done = false;
	
	/**
	 * Constructor.
	 * Loads cache file. 
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_cache = new \IF_JsonObjectStorage(
				\svnadmin\core\Engine::getInstance()->getConfig()
				->getValue('Ldap', 'CacheFile', './data/ldap.cache.json')
			);
	}
	
	/**
	 * Gets the singleton instance of this class.
	 * 
	 * @return \svnadmin\providers\ldap\CachedLdapUserViewProvider 
	 */
	public static function getInstance()
	{
		if (self::$_instance == null) {
			self::$_instance = new CachedLdapUserViewProvider();
		}
		return self::$_instance;
	}
	
	public function init()
	{
		if (!$this->_init_done) {
			$this->_init_done = true;
			\svnadmin\providers\AuthFileGroupAndPathProvider::getInstance()->init();
		}
		return parent::init();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IViewProvider::isUpdateable()
	 */
	public function isUpdateable()
	{
		return true;
	}
	
	/**
	 * Update the SVNAuthFile with data from LDAP server.
	 * @see svnadmin\core\interfaces.IViewProvider::update()
	 */
	public function update()
	{
		if (!$this->_update_done) {
			$this->_update_done = true;
			
			// Get all users from LDAP and save them to cache.
			$users = parent::getUsers(false);
			$this->_cache->setData("users", $users);
			$this->_cache->save();
			
			return parent::update();
		}
		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IUserViewProvider::getUsers()
	 */
	public function getUsers($withStarUser=true)
	{
		$cached_users = $this->_cache->getData("users");
		$users = array();
		
		for ($i = 0; $i < count($cached_users); ++$i) {
			$o = $this->_cache->objectCast($cached_users[$i], '\svnadmin\core\entities\User');
			$users[] = $o;
		}
		
		if ($withStarUser) {
			$o = new \svnadmin\core\entities\User;
			$o->id = '*';
			$o->name = '*';
			$users[] = $o;
		}
		
		return $users;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IUserViewProvider::userExists()
	 */
	public function userExists($user)
	{
		foreach ($this->getUsers() as $o) {
			if ($o->name == $user->name) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getGroups()
	 */
	public function getGroups()
	{
		return \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance()->getGroups();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::groupExists()
	 */
	public function groupExists($objGroup)
	{
		return \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance()->groupExists($objGroup);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getGroupsOfUser()
	 */
	public function getGroupsOfUser($objUser)
	{
		return \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance()->getGroupsOfUser($objUser);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getUsersOfGroup()
	 */
	public function getUsersOfGroup($objGroup)
	{
		return \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance()->getUsersOfGroup($objGroup);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::isUserInGroup()
	 */
	public function isUserInGroup($objUser, $objGroup)
	{
		return \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance()->isUserInGroup($objUser, $objGroup);
	}
}