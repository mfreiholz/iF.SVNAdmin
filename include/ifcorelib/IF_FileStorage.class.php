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
 * Saves files on local file system. Each instance has its own working directory
 * which is the place where the pictures will be stored. The instance creates folders
 * for each month where pictures are stored.
 * 
 * <b>Note:</b> Each function offers the parameter $timestamp. If you don't want that your files
 * are organised in date-formated subfolders, do not use this parameter.
 * 
 * For example:
 * $instance = new IF_FileStorage( "/var/gallery_data" );
 *
 */
class IF_FileStorage
{
	private $m_workingDir = NULL;
	
	/**
	 * Creates a new instance of this class with the given working directory.
	 *
	 * @param string $dir the working directory for the created instance.
	 */
	public function __construct( $dir )
	{
		if( !empty( $dir ) )
		{
			// Set working directory.
			$this->m_workingDir = $dir;
		}
		else
		{
			// Set working directory to current directory.
			$this->m_workingDir = "";
		}
	}
	
	/**
	 * Gets the store path of the given file with associated timestamp.
	 * If the parameter $bCreate is set to true, the subfolder will be
	 * created if it does not exist.
	 *
	 * @param bool create
	 * @param long $timestamp
	 * 
	 * @return string
	 */
	private function getStoreFolderPath( $bCreate = FALSE , $timestamp = NULL )
	{
		// The path, where the file will be saved.
		$storePath = $this->m_workingDir;
		
		if( !empty( $timestamp ) )
		{
			// Create subfolder with name of the current year and month.
			// Example: /2007-12/
			$subfolder = date( "Y-m" , $timestamp );
			$storePath = $this->m_workingDir . "/" . $subfolder;
			
			// Create the subfolder?
			if( $bCreate )
			{
				// Create IF_File object from storePath to find out whether the folder exists.
				$oStorePath = new IF_File( $storePath );
				
				// Do the subfolder exists?
				if( !$oStorePath->exists() )
				{
					// Create the subdirectory.
					if( !mkdir( $storePath ) )
					{
						throw new Exception( "Can not create subfolder: " . $oSaveDir->getAbsolutePath() );
					}
				}
			}
		}
		
		return $storePath;
	}
	
	/**
	 * Stores the given file $oFile into the CWD of the instance.
	 * If the timestamp is not 'NULL', the timestamp month and year
	 * will be used to save the file into a subfolder.
	 *
	 * @param IF_File $oFile
	 * @param long $timestamp
	 * 
	 * @return bool
	 * 
	 * @throws Exception
	 */
	public function store( $oFile , $timestamp = NULL )
	{
		// Is $oFile given?
		if( !empty( $oFile ) )
		{
			// Do the file exits?
			if( $oFile->exists() )
			{
				// The path where the file will be stored.
				$storePath = self::getStoreFolderPath( TRUE , $timestamp );
				
				// Copy source file to destination store path.
				$oDestFile = new IF_File( $storePath . "/" . $oFile->getName() );
				
				if( $oFile->copyTo( $oDestFile ) )
				{
					return TRUE;
				}
				else
				{
					throw new Exception( "Can not copy file \"" . $oFile->getAbsolutePath() . "\" to \"" .
						$oDestFile->getAbsolutePath() . "\"" );
				}
			}
			else
			{
				throw new Exception( "The file " . $oFile->getAbsolutePath() . " does not exist." );
			}
		}
		else
		{
			throw new Exception( "Parameter 1 must be from type IF_File" , 1 );
		}
	}
	
	/**
	 * Gets the path to the file which the caller of this method wants.
	 *
	 * @param string $strFilename
	 * @param long $timestamp
	 */
	public function load( $strFilename , $timestamp = NULL )
	{
		// Parameter must be given.
		if( !empty( $strFilename ) )
		{
			// Get the store folder of the file.
			$storeFolder = self::getStoreFolderPath( FALSE , $timestamp );
			
			// Create a file object and return the relative path.
			$oStoredFile = new IF_File( $storeFolder . "/" . $strFilename );
			
			if( $oStoredFile->exists() )
			{
				return $oStoredFile->getPath();
			}
			else
			{
				// Throw excpetion, because the file doesn't exist.
				throw new Exception( "The file doesn't exist: " . $oStoredFile->getAbsolutePath() );
			}
		}
	}
	
}
?>