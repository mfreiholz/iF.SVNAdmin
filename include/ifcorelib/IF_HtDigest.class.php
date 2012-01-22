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
 * The following global configuration defines are available for this class:
 * - IF_HtPDigest_RealmName
 */
class IF_HtDigest
{
	// The digest realm
	private $m_realm = '';
	
	// Holds the user file as an array (username=>pwd-hash) that are part of this realm
	private $m_data = array();
	private $m_rawData = array();
	
	// Holds the user-password-mappings that are not part of this realm

	// Holds the path to the user authentication file.
	private $m_userfile = NULL;

	// Holds the error number, if a error occured.
	private $m_errno = 0;

	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Creates a new instance of this class and assigns the given
	 * file as "passwd" file to it.
	 * 
	 * @param string $userfile
	 */
	public function __construct( $userfile, $realm )
	{
		$this->m_userfile = $userfile;
		$this->m_realm = $realm;
	}

	/**
	 * Loads the file content and does some init operations.
	 * 
	 * @return void
	 */
  public function init()
  {
  	$b = self::parseUserFile( $this->m_userfile );
  	return $b;
  }

  public function errno()
  {
  	return $this->m_errno;
  }

  public function error()
  {
  	switch( $this->m_errno )
  	{
  		case 1: return "The user authentication file does not exist.";
  		case 2: return "No READ permission on the user authentication file.";
  		case 10: return "The user already exists.";
  		case 11: return "The user does not exist.";
  		default: return "No error occured.";
  	}
  }

  /**
   * Gets a list filled with all users in the file.
   * 
   * @return array<string> List of users.
   */
  public function getUserList()
  {
  	$retUsers = array();
  	foreach( $this->m_data as $username=>$pass )
  	{
  		array_push( $retUsers, $username );
  	}
  	return $retUsers;
  }

  /**
   * Creates a new user inside the object.
   * Call writeToFile(...) to save the user to disc.
   * 
   * @param string $username
   * @param string $password
   * @param bool $crypt
   */
  public function createUser( $username, $password, $crypt = true )
  {
    if( self::userExists( $username ) )
    {
    	// The user already exists.
      $this->m_errno = 10;
      return false;
    }

  	// Add the user to the holded data array.
    $this->m_data[$username] = self::digest_password($username, $password);
    return true;
  }

  public function changePassword($username, $newpass)
  {
    if (self::userExists($username) && !empty($newpass))
    {
      $this->m_data[$username] = self::digest_password($username, $newpass);
      return true;
    }
    else
      return false;
  }

  public function deleteUser( $username )
  {
    if( !self::userExists( $username ) )
    {
    	// The user does not exists.
    	$this->m_errno = 11;
    	return false;
    }
    else
    {
    	// Unset user.
      unset( $this->m_data[$username] );
      return true;
    }
  }

  public function userExists( $username )
  {
  	if( isset( $this->m_data[$username] ) && !empty( $this->m_data[$username] ) )
  	{
  		return true;
  	}
  	else
  	{
  		return false;
  	}
  }
  
  public function authenticate( $username, $password )
  {
	  if(self::userExists( $username ))
		{
	    $pass = &$this->m_data[$username];
	    $password_digest = self::digest_password($username, $password);
	
	    return ($password_digest == $pass);
		}
    // User not found.
    return false;
  }

  //////////////////////////////////////////////////////////////////////////////

  /**
   * Parses the user file and saves the data in a localy holded array, which
   * can be accessed by the public functions of this class.
   *
   * @param striing $userfile The file to parse.
   * @return bool
   */
  private function parseUserFile( $userfile )
  {
    if( !file_exists( $userfile ) )
    {
    	// File does not exist.
    	$this->m_errno = 1;
    	return false;
    }

    if( !is_readable( $userfile  ) )
    {
    	// No permission to read the file.
    	$this->m_errno = 2;
    	return false;
    }

    // Open file in read-mode.
    $fh = fopen( $userfile, "r" );
    flock( $fh, LOCK_SH );

    // Read each line as one user entry.
    while( !feof( $fh ) )
    {
      $line = fgets( $fh );
      $line = trim( $line );

      if( empty( $line ) )
      {
      	continue;
      }

      // Split the line by ':'.
      // [0] = Username
      // [1] = Realm
      // [2] = Hashed password
      $values = explode( ":", $line );

			if (count($values) == 3)
			{
			 if ($values[1] == $this->m_realm)
			 {
			   $this->m_data[$values[0]] = $values[2];
			 }
			 else
			 {
			   $this->m_rawData[] = $line;
			 }
			}
    }
    flock( $fh, LOCK_UN );
    fclose( $fh );
    return true;
  }

  /**
   * Saves the local m_data, which holds the user information to the given file.
   *
   * @param $filename
   * @return unknown_type
   */
  public function writeToFile( $filename = NULL )
  {
    if( $filename == NULL )
    {
      $filename = $this->m_userfile;
    }
  
    // Open file and write the array of users to it.
    $fh = fopen( $filename, "w" );
    flock( $fh, LOCK_EX );
    foreach( $this->m_data as $usr=>$pwd )
    {
      $line = $usr.":".$this->m_realm.':'.$pwd."\n";
      fwrite( $fh, $line, strlen( $line ) );
    }
	
    foreach( $this->m_rawData as $line )
    {
      $line = $line."\n";
      fwrite( $fh, $line, strlen( $line ) );
    }
    flock( $fh, LOCK_UN );
    fclose( $fh );
    return true;
  }
  
  //////////////////////////////////////////////////////////////////////////////
  // Additional functions.
  //////////////////////////////////////////////////////////////////////////////
  
  private function digest_password($username, $password)
  {
    return md5($username.":".$this->m_realm.':'.$password);
  }
 
}
?>