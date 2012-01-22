<?php
namespace svnadmin\core\interfaces;

interface IAclManager
{
	public function init();

	/**
	 * Checks whether the user has the given permission "$action" on
	 * the resource "$module".
	 * @param string $user
	 * @param string $module
	 * @param string $action
	 */
	public function hasPermission($objUser, $module, $action);

	/**
	 * Gets the roles of the user.
	 * @param User $user
	 * @return array<IF_ACLRole>
	 */
	public function getRolesOfUser($objUser);

	/**
	 * Gets all available roles.
	 * @return array<IF_ACLRole>
	 */
	public function getRoles();

    /**
	 * Assigns the given user to the given role.
	 * @param <type> $objUser
	 * @param <type> $objRole
	 * @return bool
	 */
	public function assignUserToRole($objUser, $objRole);

	/**
	 * Removes the user from a role.
	 * @param <type> $objUser
	 * @param <type> $objRole
	 * @return bool
	 */
	public function removeUserFromRole($objUser, $objRole);

	/**
	 * Saves the ACL.
	 */
	public function save();

	/**
	 * Loads the ACL.
	 */
	public function load();
}
?>
