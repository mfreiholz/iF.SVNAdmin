<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule( !$appEngine->isGroupEditActive() );

// Parameters.
$selusers = get_request_var('selusers');
$selgroups = get_request_var('selgroups');
$selsubgroups = get_request_var('selsubgroups');

if ($selusers == NULL)
	$selusers = get_request_var("selected_users");

if ($selgroups == NULL)
	$selgroups = get_request_var("selected_groups");

if ($selsubgroups == NULL)
	$selsubgroups = get_request_var("selected_subgroups");

if ($selusers != NULL && count($selusers) > 0 && empty($selusers[0]))
{
	$selusers = NULL;
}

if ($selgroups != NULL && count($selgroups) > 0 && empty($selgroups[0]))
{
	$selgroups = NULL;
}

if ($selsubgroups != NULL && count($selsubgroups) > 0 && empty($selsubgroups[0]))
{
	$selsubgroups = NULL;
}

// Validate.
if( ($selusers == NULL && $selsubgroups == NULL) || $selgroups == NULL )
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one user and one group.")));
}
// Do assignments.
else
{
	// Count of: All, Done, Failed
	$cntAll = count($selgroups) * count($selusers);
	try
	{
		// Iterate all selected users and groups.
		for( $i=0; $i<count($selgroups); $i++ )
		{
			$oG = new \svnadmin\core\entities\Group;
			$oG->id = $selgroups[$i];
			$oG->name = $selgroups[$i];

			for( $k=0; $k<count($selsubgroups); $k++ )
			{
				$oS = new \svnadmin\core\entities\Group;
				$oS->id = $selsubgroups[$k];
				$oS->name = $selsubgroups[$k];
				if ($oG->name != $oS->name)
				{
					if ($appEngine->getGroupEditProvider()->assignSubgroupToGroup($oS, $oG))
					{
						$appEngine->getGroupEditProvider()->save();
						$appEngine->addMessage(tr("The group %0 is now a member of group %1", array($oS->name, $oG->name)));
					}
					else
					{
						$appEngine->addException(new Exception(tr("Can not add group %0 as member of group %1.", array($oS->name, $oG->name))));
					}
				}
				else
				{
					$appEngine->addException(new Exception(tr("Can not add group %0 as member of group %1, is same group.", array($oS->name, $oG->name))));
				}
			 } //for

			for( $j=0; $j<count($selusers); $j++ )
			{
				$oU = new \svnadmin\core\entities\User;
				$oU->id = $selusers[$j];
				$oU->name = $selusers[$j];

				if ($appEngine->getGroupEditProvider()->assignUserToGroup($oU, $oG))
				{
					$appEngine->getGroupEditProvider()->save();
					$appEngine->addMessage(tr("The user %0 is now a member of group %1", array($oU->name, $oG->name)));
				}
				else
				{
					$appEngine->addException(new Exception(tr("Can not add user %0 as member of group %1.", array($oU->name, $oG->name))));
				}
			} //for
		} //for
	}
	catch (Exception $ex)
	{
		$appEngine->addException($ex);
	}
}
?>