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
if (!defined("ACTION_HANDLING"))
{
	die("HaHa!");
}

$E = \svnadmin\core\Engine::getInstance();

//
// Authentication
//

if (!$E->isViewUpdateable())
{
	$E->forwardError(ERROR_INVALID_MODULE);
}

//
// Action
// Update all IViewProvider instances of the engine.
//

try {
	// List of update providers.
	$providers = array(
		"User-View" => $E->getProvider(PROVIDER_USER_VIEW),
		"Group-View" => $E->getProvider(PROVIDER_GROUP_VIEW),
		"AccessPath-View" => $E->getProvider(PROVIDER_ACCESSPATH_VIEW)
	);

	foreach ($providers as $type => &$prov)
	{
		try {
			if ($prov != null && $prov->isUpdateable())
			{
				if ($prov->update())
					$E->addMessage(tr("Update successful: %0", array($type)));
				else
					throw new Exception(tr("An unknown error occured. Check your configuration, please."));
			}
		}
		catch (Exception $except) {
			$E->addException($except);
		}
	}
}
catch (Exception $excep) {
	$E->addException($excep);
}
?>