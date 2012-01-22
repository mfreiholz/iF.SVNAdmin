<?php
/**
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
 * Provides functionality to handle the contents of a ".htpasswd" file.
 *
 * The following password encryptions are supported:
 * - define("IF_HtPasswd_DefaultCrypt", "CRYPT")
 * - define("IF_HtPasswd_DefaultCrypt", "SHA1")
 * - define("IF_HtPasswd_DefaultCrypt", "MD5")
 *
 * @author Manuel Freiholz, insaneFactory.com
 */
class IF_HtPasswd
{
	// Holds the user file as an array (username=>encrypted-password)
	private $m_data = array();

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
	public function __construct( $userfile )
	{
		$this->m_userfile = $userfile;
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

  	// Should the password being crypted?
  	if( $crypt == true )
  	{
  		$password = self::crypt_default( $password ); // Force MD5 as salt!
  	}

  	// Add the user to the holded data array.
    $this->m_data[$username] = $password;
    return true;
  }

  public function changePassword($username, $newpass, $crypt=true)
  {
    if (self::userExists($username))
    {
      if ($crypt && !empty($newpass))
      {
        $newpass = self::crypt_default($newpass);
      }
      $this->m_data[$username] = $newpass;
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
  	// Find the user.
    foreach( $this->m_data as $usr=>&$pass )
    {
    	// Found user.
      if( $usr == $username )
      {
      	// Find out which encryption type is used.
      	// SHA
      	if (strpos($pass, "{SHA}") === 0)
      	{
      		$password_crypted = self::crypt_sha($password);

//      		echo "Type: SHA\n";
//          echo "Password-In-File: ".$pass."\n";
//      		echo "Password-Crypted: ".$password_crypted."\n";

      		if ($password_crypted == $pass)
      		  return true;
      		else
      		  return false;
      	}
      	// MD5
      	elseif (strpos($pass, '$apr1$') === 0 && preg_match('/\$(.*)\$(.*)\$(.*)/', $pass, $matches) > 0)
      	{
      		// Stick togehter the salt of the password.
      		$salt = $matches[2];

      		// Crypt the user entered password.
      		$password_crypted = self::crypt_apr1_md5($password, $salt);

//          echo "Type: MD5\n";
//          echo "Password-In-File: ".$pass."\n";
//          echo "Password-Salt   : ".$salt."\n";
//          echo "Password-Crypted: ".$password_crypted."\n";

      		if ($password_crypted == $pass)
      		  return true;
      		else
      		  return false;
      	}
      	// CRYPT (only unix)
      	else
      	{
      		// The different length of salts.
      		$crypt_types = array("STD-DES" => 2, "EXT-DES" => 9, "MD5" => 12, "BLOWFISH" => 16);

      		foreach ($crypt_types as $type=>$len)
      		{
      			$salt = substr($pass, 0, $len);
      			$password_crypted = self::crypt_unix($password, $salt);

//	          echo "Type: CRYPT (Unix only)\n";
//	          echo "Hash-Type: ".$type."\n";
//	          echo "Password-In-File: ".$pass."\n";
//	          echo "Password-Salt   : ".$salt."\n";
//	          echo "Password-Crypted: ".$password_crypted."\n";
//	          echo "\n";

	          if ($password_crypted == $pass)
	            return true;
	          else
	            continue;
      		}
      		return false;
      	}
      }
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
      // [1] = Crypted password
      $values = explode( ":", $line );

      if( count( $values ) == 2 )
      {
        $this->m_data[$values[0]] = $values[1];
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
      $line = $usr.":".$pwd."\n";
      fwrite( $fh, $line, strlen( $line ) );
    }
    flock( $fh, LOCK_UN );
    fclose( $fh );
    return true;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Additional crypt functions.
  //////////////////////////////////////////////////////////////////////////////

  /**
   * @param string $plainpasswd
   */
  private function crypt_default($plainpasswd)
  {
  	$type = "";
  	if (defined("IF_HtPasswd_DefaultCrypt"))
  	{
  		$type = IF_HtPasswd_DefaultCrypt;
  	}

  	switch ($type)
  	{
  		case "CRYPT":
  			return self::crypt_unix($plainpasswd);

  		case "SHA1":
  			return self::crypt_sha($plainpasswd);

  		case "MD5":
  			return self::crypt_apr1_md5($plainpasswd);

  		default:
  			return self::crypt_apr1_md5($plainpasswd);
  	}
  }

  /**
   * Creates a default unix crypt hash of the given password with the
   * specified salt. If no salt is given then the function will use its own
   * generated salt.
   *
   * @param string $plainpasswd
   * @param string $salt
   * @return string
   */
  private function crypt_unix($plainpasswd, $salt = "")
  {
  	$password_crypted = "";
  	if (empty($salt))
  	{
  		$password_crypted = crypt($plainpasswd);
  	}
  	else
  	{
  		$password_crypted = crypt($plainpasswd, $salt);
  	}
  	return $password_crypted;
  }

  /**
   * Creates an SHA1 generated hash of the given plain text password.
   *
   * @param string $plainpasswd
   * @return string
   */
  private function crypt_sha($plainpasswd)
  {
  	$password_crypted = "{SHA}".base64_encode(sha1($plainpasswd, true));
  	return $password_crypted;
  }

  /**
   * Creates an hash of the given plain text password with the specified
   * salt. If no salt is given then the function will use it's own random
   * generated salt.
   *
   * @param string $plainpasswd
   * @param string $salt
   * @return string Hash of the plain password.
   */
	private function crypt_apr1_md5($plainpasswd, $salt = "")
	{
		// Use default salt?
		$translate_to = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		if (empty($salt))
		{
			$salt = substr(str_shuffle($translate_to), 0, 8);
		}

		// Password length.
		$len = strlen($plainpasswd);
		$text = $plainpasswd.'$apr1$'.$salt;
		$bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));

		for($i=$len; $i>0; $i-=16)
		{
			$text.= substr($bin, 0, min(16, $i));
		}

		for($i=$len; $i>0; $i>>=1)
		{
			$text.= ($i & 1) ? chr(0) : $plainpasswd{0};
		}

		$bin = pack("H32", md5($text));

		for($i = 0; $i < 1000; $i++)
		{
			$new = ($i & 1) ? $plainpasswd : $bin;
			if ($i % 3) $new .= $salt;
			if ($i % 7) $new .= $plainpasswd;
			$new .= ($i & 1) ? $bin : $plainpasswd;
			$bin = pack("H32", md5($new));
		}

		$tmp = "";
		for ($i = 0; $i < 5; $i++)
		{
			$k = $i + 6;
			$j = $i + 12;
			if ($j == 16) $j = 5;
			$tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
		}

		$tmp = chr(0).chr(0).$bin[11].$tmp;
		$tmp = strtr(
			  strrev(substr(base64_encode($tmp), 2)),
			  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
			  $translate_to
		  );

		return "$"."apr1"."$".$salt."$".$tmp;
	}
}
?>