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
if (!defined('ACTION_HANDLING'))
{
  die("HaHa!");
}

$appEngine->forwardInvalidModule( !$appEngine->isGroupEditActive() );


$selected = get_request_var('selected_groups');
if($selected == NULL)
{
  $appEngine->addException(new ValidationException(tr("You have to select at least one group.")));
}
else
{
  try
  {
    // Iterate all selected users and delete them.
    for($i=0; $i<count($selected); $i++)
    {
      $g = new \svnadmin\core\entities\Group;
      $g->id = $selected[$i];
      $g->name = $selected[$i];

      if ($appEngine->deleteGroup($g))
      {
        $appEngine->addMessage(tr("Deleted group %0 successfully.", array($g->name)));
      }
      else
      {
        $appEngine->addException(new Exception(tr("Can not remove group %0.", array($g->name))));
      }
    }
  }
  catch (Exception $ex)
  {
    $appEngine->addException($ex);
  }
}
?>
