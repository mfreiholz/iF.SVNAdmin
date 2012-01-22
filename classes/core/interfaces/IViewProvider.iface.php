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
namespace svnadmin\core\interfaces
{
  interface IViewProvider extends IProvider
  {
    /**
     * Indicates whether the provider is updateable and
     * it would have any effect to call the 'update()' method.
     *
     * @return bool
     */
    public function isUpdateable();
    
    /**
     * Updates the implementing provider.
     * Should return as default, if no update is available.
     *
     * @return bool
     */
    public function update();
  }
}
?>