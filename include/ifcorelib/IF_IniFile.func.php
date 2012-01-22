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
/**
 * Parses the given ini file and returns the values of it in an array.
 *
 * @param string $filename
 * @return array
 */
function if_parse_ini_file( $filename )
{
  if( !file_exists( $filename ) || !is_file( $filename ) )
  {
    return false;
  }

  $arr = array();

  $fh = fopen( $filename, "r" );
  if( !flock( $fh, LOCK_SH ) )
    return false;
  
  $last_section_name = NULL;
  while( !feof( $fh ) )
  {
    $line = fgets( $fh );
    $line = trim( $line );

    if( empty( $line ) )
    {
      continue;
    }
    
    // Skip comments.
    if( strpos( $line, '#' ) === 0 )
    {
      continue;
    }
    else if( strpos( $line, ';' ) === 0 )
    {
      continue;
    }

    if( substr( $line, 0, 1 ) == '[' ) // Section header.
    {
      $section_name = substr( $line, 1, strlen($line)-2 );
      $arr[$section_name] = array();
      $last_section_name = $section_name;
      continue;
    }
    else // Key,Value pairs of last section header.
    {
      $splits = explode("=", $line, 2 );
      $key = trim( $splits[0] );
      $val = NULL;

      if( count($splits) > 1 )
      {
        $val = trim( $splits[1] );
      }
      $arr[$last_section_name][$key] = $val;
    }
  }
  flock( $fh, LOCK_UN );
  fclose( $fh );
  return $arr;
}

/**
 * Writes the array to the given file in ini format.
 * @param $filename
 * @param $iniArray
 * @return unknown_type
 */
function if_write_ini_file( $dest_filename, $data )
{
	if( !is_array( $data ) )
	{
		return false;
	}

  if( !file_exists( $dest_filename ) )
  {
    // try to create the file.
    if( !touch( $dest_filename ) )
    {
      return false; // can not create file.
    }
  }

  $fh = fopen( $dest_filename, "w" );
  flock( $fh, LOCK_EX );
  foreach( $data as $section_name=>$section_data ) // iterate sections.
  {
    fwrite( $fh, "\n[$section_name]\n" );

    if( is_array( $section_data ) )
    {
      foreach( $section_data as $key=>$val ) // iterate key/value pairs of section.
      {
        fwrite( $fh, "$key=$val\n" );
      }
    }
  }
  flock( $fh, LOCK_UN );
  fclose( $fh );
  return true;
}

/**
 * Creates a backup of the given $filename in the $backup_folder.
 * @param $filename
 * @param $backup_folder
 * @return unknown_type
 */
function if_backup_file( $filename, $backup_folder )
{
  if( !file_exists( $filename ) || !is_file( $filename ) )
  {
    return false; // the file does not exist.
  }

  if( !file_exists( $backup_folder ) || !is_dir( $backup_folder ) )
  {
    return false; // the backup folder does not exist.
  }

  // get only the filename from value if it is a long path.
  $filename_base = basename( $filename );

  // create the backup filename.
  $date_string = date( "Y-m-d_H-i-s" );
  $backup_filename_base = $filename_base . "." . $date_string;
  $dest_backup_file = $backup_folder . "/" . $backup_filename_base;

  $flag = copy( $filename, $dest_backup_file );
  if( $flag != true )
  {
    return false; // can not copy file.
  }
  return $dest_backup_file;
}
?>