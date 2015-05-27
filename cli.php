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
require_once("include/config.inc.php");

/*
 * Some helper functions.
 */

function getArgument($argv, $name, $default=null)
{
	if (empty($argv))
		return $default;

	$cnt = count($argv);
	for ($i = 1; $i < $cnt; ++$i)
	{
		if (strcasecmp($argv[$i], $name) === 0)
		{
			if (($cnt - 1) >= ($i + 1))
			{
				if (!empty($argv[$i+1]))
					return $argv[$i+1];
			}
			break;
		}
	}
	return $default;
}

function printUsage()
{
	$E = \svnadmin\core\Engine::getInstance();
	$s =
		"Command line interface of iF.SVNAdmin\n".
		"Version: ".$E->getAppVersionString()."\n".
		"Usage:\n".
		"\tphp cli.php --mode [mode]\n".
		"\n".
		"Available modes:\n".
		"\tupdate                Updates all updateable data providers (e.g.: ldap).\n".
		"\tlicense               Prints out the license of this application.\n".
		"\n".
		"! Important usage notice !\n".
		"Make sure that the current working directory (PWD/CWD) where the script ".
		"is being executed is the root of the iF.SVNAdmin application ".
		"(e.g.: /var/www/svnadmin/).".
		"\n"
	;
	print($s);
}

/*
 * CLI
 */

$mode = getArgument($argv, "--mode");

if ($mode == "update")
{
	$E = \svnadmin\core\Engine::getInstance();

	if (!$E->isViewUpdateable())
	{
		print("No updateable data provider configured.");
		exit(0);
	}

	// List of update providers.
	$providers = array(
		"User-View" => $E->getProvider(PROVIDER_USER_VIEW),
		"Group-View" => $E->getProvider(PROVIDER_GROUP_VIEW),
		"AccessPath-View" => $E->getProvider(PROVIDER_ACCESSPATH_VIEW)
	);

	foreach ($providers as $type => &$prov)
	{
		if ($prov != null && $prov->isUpdateable())
		{
			if ($prov->update())
				print("Update successful: ".$type."\n");
			else
				print("Error during update of ".$type."\n");
		}
	}
}
elseif ($mode == "license")
{
	// Print out license.
	$data = file_get_contents(("license.txt"));
	print ($data);
	exit(0);
}
else
{
	printUsage();
	exit(0);
}
?>