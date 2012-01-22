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
class IF_Template_Exception extends Exception
{
  public function __construct($message="", $code=0, Exception $previous=null)
  {
    parent::__construct($message, $code, $previous);
  }
}

/**
 * This class can be used to load a template file and replace variabes
 * or loops in it.
 *
 * @author Manuel Freiholz / www.gainwar.de
 * @since 30.12.2007
 */
class IF_Template
{
	// The string which is on the left of a variable.
	private $m_leftDelimiter = '${';
	
	// The string which is on the right of a variable.
	private $m_rightDelimiter = '}';
	
	// The IF_File class of the template file.
	private $m_templateFile = NULL;
	
	// The file content of the template file.
	private $m_templateContent = NULL;
	
	// Used replacements for the patterns.
	public $m_replacements = array();
	
	// Defines for if-statements.
	private $m_defines = array();

  // Translator used for variables.
  private $m_translator = NULL;

  // Translation variable delimiters.
  private $m_leftTrDelimiter = 'TR{';
  private $m_rightTrDelimiter = '}}';

  // ACL for defines.
  // The object must provide function: hasPermission(module, action)
  private $m_acl = NULL;
	
	/**
	 * Creates a new instance of this class.
	 *
	 */
	public function __construct()
	{
	}
	
	/**
	 * Loads the given template from file.
	 *
	 * @param IF_File $strFile
	 * 
	 * @return bool
	 */
	public function loadFromFile( $templateFile )
	{
		$this->m_templateFile = $templateFile;
		
		// Check whether the file exists.
		if( $this->m_templateFile->exists() )
		{
			// Read content from file.
			$this->m_templateContent = file_get_contents( $this->m_templateFile->getPath() );
			
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Loads the template from the given string object.
	 *
	 * @param string $templateString
	 * 
	 * @return bool
	 */
	public function loadFromString( $templateString )
	{
		if( !empty($templateString) )
		{
			// Init templateContent.
			$this->m_templateContent = $templateString;
			
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

  /**
   * Sets the translator, which should be used for translation tags.
   * @param IF_Translator $translatorObject
   */
  public function setTranslator($translatorObject)
  {
    $this->m_translator = $translatorObject;
  }

  /**
   * Sets the ACL object, which resolves the ACL_* defines.
   * @param IF_ACL $aclObject
   */
  public function setAcl($aclObject)
  {
    $this->m_acl = $aclObject;
  }
	
	/**
	 * Adds a new replacement to the template class.
	 * All replacements will replace the pattern in the template string.
	 * 
	 * Example:
	 * 1. Parameter: "NAME"
	 * 2. Parameter: "Foo"
	 * 
	 * This replacement replaces the string "${NAME}" into "Foo".
	 *
	 * @param string $strPattern
	 * @param string $strReplacement
	 * 
	 * @return bool
	 */
	public function addReplacement( $strPattern , $replacement )
	{
		if( !empty($strPattern) ) // Using != NULL, because empty strings are valid
		{
			$this->m_replacements[$strPattern] = $replacement;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Adds a new define which can be used for IF statements.
	 *
	 * @param string $strDefine
	 * @return bool
	 */
	public function addDefine( $strDefine )
	{
    if( !empty($strDefine) )
    {
    	if (!in_array($strDefine, $this->m_defines))
    	{
    		$this->m_defines[] = $strDefine;
    	}
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Trys to resolve the given ACL-Condition.
   * @param string $aclDefStr The $aclCondition string (ACL_*)
   */
  private function resolveAclDefine($aclCondition)
  {
    // 0 = ACL
    // 1 = Module name
    // 2 = Action name
    $parts = explode("_", $aclCondition);
    return $this->m_acl->hasPermission($parts[1], $parts[2]);
  }

  /**
   * Resolves the condition of an IFDEF block.
   * @param string $condition
   * @return bool
   */
  private function resolveDefineCondition($conditionString)
  {
    $conditionString = trim($conditionString);
    $evalOk = FALSE;

    // Array which holds the single conditions and operators.
    $conditions = explode(" ", $conditionString);
    $conditionsCount = count($conditions);
    for ($i=0; $i<$conditionsCount; $i++)
    {
      // The current condition: ACL_USERS_VIEW or !ACL_USERS_VIEW
      $con = trim($conditions[$i]);

      // Is it a ! condition?
      $reverse = FALSE;
      if (substr($con, 0, 1) == "!")
      {
        $reverse = TRUE;
        $con = substr($con, 1);
      }

      // Is it an ACL define?
      $isAclDef = FALSE;
      if (substr($con, 0, 3) == "ACL")
      {
        $isAclDef = TRUE;
        if ($this->m_acl == NULL)
          $evalOk = TRUE;
        else
          $evalOk = self::resolveAclDefine($con);
      }
      // Search the condition in m_defines array.
      // Not an ACL define.
      else
      {
        if (array_search($con, $this->m_defines) !== FALSE)
          $evalOk = TRUE;
        else
          $evalOk = FALSE;
      }

      // Reverse evalulation?
      if ($reverse)
        if ($evalOk)
          $evalOk = FALSE;
        else
          $evalOk = TRUE;

      // Is the next condition an && or || operator?
      if ($conditionsCount >= ($i+2))
      {
        $i++; // Points to operator now.
        $operator = trim($conditions[$i]);
        if ($operator == "&&")
        {
          if ($evalOk == FALSE)
            return FALSE;
          else
            continue;
        }
        else if($operator == "||")
        {
          if ($evalOk == FALSE)
          {
            $evalOk == TRUE; // Set to true, because the next statement gets the chance to make the evalulation valid.
            continue;
          }
          else
          {
            // Skip all coming OR statements and directly return.
            // Note: This means that no other && statements can follow if an || operator occured.
            return TRUE;
          }
        }
        else
          throw new IF_Template_Exception("Missing operator between defines in IFDEF statement.");
      }

      // Break if the condition is not evaluated.
      if ($evalOk == FALSE)
        return FALSE;
    }
    return $evalOk;
  }

  /**
   * Searches for all translation strings in the given $text and trys
   * to replace them with a translated string.
   * @param <type> $text
   */
  public function doTranslations(&$text)
  {
    $offset = 0;
    $leftLen = strlen($this->m_leftTrDelimiter);
    $rightLen = strlen($this->m_rightTrDelimiter);
    do
    {
      // Find the beginning of the translation functions.
      $leftPos = strpos($text, $this->m_leftTrDelimiter, $offset);
      if ($leftPos === FALSE)
        break;

      // Find the end of the translation function.
      $rightPos = strpos($text, $this->m_rightTrDelimiter, $leftPos+$leftLen);
      $trKeyLen = $rightPos - $leftPos;

      // Read the string from translation function to translate it.
      $trKey = substr($text, $leftPos+$leftLen, $trKeyLen-($leftLen));

      // Use the IF_Translator to translate the string.
      $value = NULL;
      if ($this->m_translator != NULL)
        $value = $this->m_translator->tr($trKey);
      else
        $value = $trKey;

      // Insert the translation now.
      $text = str_replace(substr($text, $leftPos, $trKeyLen+$rightLen), $value, $text);

      // The new offset to find the next occurance.
      $offset = $leftPos+1;
    }
    while(true);
  }
	
	/**
	 * Replaces the added patterns with the replacements.
	 *
	 * @param string The text in which are variables to replace.
	 * @param array Associative array with patterns and values.
	 * 
	 * @return string The new template content with replaced variables.
	 */
	private function doReplacements( $strText , $vars )
	{
		// Check whether patterns and replacements are given.
		if( ( count($vars) > 0 ) )
		{
			// The keys (patterns) of the template.
			$arrKeys = array_keys( $vars );
			
			for( $i = 0; $i < count( $arrKeys ); $i++ )
			{
				$strPattern = $arrKeys[$i];
				$value = $vars[$strPattern];
				
				if( is_scalar( $value ) )
				{
					// Add delimiters to the search string (pattern).
					$strPattern = $this->m_leftDelimiter . $strPattern . $this->m_rightDelimiter;

					// Replace the pattern with the value.
					$strText = str_replace( $strPattern , $value , $strText );
				}
				else
				{
					// The current $value is an object.. we can not handle objects.
					continue;
				}
			}
			
			return $strText;
		}
		else
		{
			return $strText;
		}
	}
	
	/**
	 * Searches for IFDEF statements and handles them.
	 * The function also supports the ACL_* defines, which are resolved
   * with the $m_acl object.
   *
   * An ACL variable looks like: ACL_Module_Action.
   *
   * @param string $text The text in which the statements.
   * @param int $iOffset The start search position.
   * @param bool $skip If this value is TRUE, then the conditions must not be
   *                   resolved. Only remove the content.
	 */
	private function doDefines(&$text, $iOffset=0, $skip=FALSE)
	{
    do
    {
      // Search for the head position of the next IFDEF statement.
      $iPosHead = strpos($text, "[{IFDEF ", $iOffset);
      if ($iPosHead === FALSE)
        break; // No match.

      // End of the head block.
      $iPosHeadEnd = strpos($text, "}]", $iPosHead);

      // Resolve condition.
      $conditionStringBase = substr($text, $iPosHead+8, $iPosHeadEnd-($iPosHead+8));
      $evalOk = FALSE;
      if ($skip === TRUE)
        $evalOk = FALSE;
      else
        $evalOk = self::resolveDefineCondition($conditionStringBase);

      // Handle sub statements of the current statement.
      $iPosFoot = strpos($text, "[{/IFDEF}]", $iPosHeadEnd);
      $iPosSubHead = strpos($text, "[{IFDEF ", $iPosHeadEnd);

      if ($iPosFoot === FALSE)
        throw new IF_Template_Exception("Template syntax error: Missing end tag of IFDEF.");

      // Looks like as there are sub statements.
      if ($iPosSubHead !== FALSE && $iPosSubHead < $iPosFoot)
      {
        // Handle the sub statements.
        self::doDefines($text, $iPosSubHead, !$evalOk);

        // All sub foots should be resolved now. Search the next foot again
        // to resolve the current statement.
        $iPosFoot = strpos($text, "[{/IFDEF}]", $iPosHeadEnd);

        if ($iPosFoot === FALSE)
          throw new IF_Template_Exception("Template syntax error: Missing end tag of IFDEF.");
      }

      $iFootLen = 10;
      
      // Read the complete IFDEF block into a new string.
      $theCompleteBlock = substr($text, $iPosHead, ($iPosFoot+$iFootLen)-$iPosHead);
      
      // Calculate the length of the IFDEF head tag.
      $iHeadLen = 8 + 2 + strlen($conditionStringBase);
      
      // Check whether the define is set.
      if ($evalOk === TRUE)
      {
        // The var is defined. Get only the content between the start and end tag.
        $blockContent = substr($text, $iPosHead+$iHeadLen, ($iPosFoot-$iPosHead)-$iHeadLen);
        // To remove the head and foot tag, replace the old $theCompleteBlock with the $blockContent.
        $text = str_replace($theCompleteBlock, $blockContent, $text);
      }
      else
      {
        // The var is NOT defined. Remove the complete block.
        $text = str_replace($theCompleteBlock, "", $text);
      }
      $iOffset = $iPosHead;
      $skip = FALSE;
    }
    while( $iPosHead );
  }
	
	/**
	 * Searches for all loops in the template content
	 * and replaces all including variables of the loops.
	 */
	private function doLoops()
	{	
		// Find out the position of the first loop-start.
		$iOffset = 0;
		
		do
		{
			// Will contain the final loop content.
			$finalLoopContent = "";
			
			// Find out the position of the next loop statement start.
			// If there are no more loop heads then $iPosHead == FALSE.
			$iPosHead = strpos( $this->m_templateContent , "[{LOOP " , $iOffset );
			
			if( $iPosHead )
			{
				// Get the position of the loop foot.
				$iPosFoot = strpos( $this->m_templateContent , "[{/LOOP}]" , $iPosHead );
				
				if( $iPosFoot )
				{
					// Read the complete loop content into a new string.
					$strLoopContent = substr(
							$this->m_templateContent , $iPosHead , $iPosFoot-$iPosHead
						);
					
					// Get the variable which is to loop from loop-head.
					$strLoopHeadPattern = "/\[\{LOOP ([a-zA-Z0-9\_]+)\}\]/";
					
					// Find variable or break, because of syntax error.
					if( preg_match( $strLoopHeadPattern , $strLoopContent , $matches ) == 1 )
					{
						// The name of the variable which is to iterate.
						$strVariableName = $matches[1];
						
						// Calculate the string length of the loop head.
						// 9 = all static signs of the loop head.
						// n = length of the variable name
						$iLoopHeadLength = 9 + strlen( $strVariableName );
						
						// Remove the loop head from content.
						$strLoopContent = substr(
								$this->m_templateContent , $iPosHead+$iLoopHeadLength ,
								($iPosFoot-$iPosHead)-$iLoopHeadLength
							);
						
						// Check whether the variable exists in the replacements array.
						$var = NULL;
						
						foreach( $this->m_replacements as $k=>$v )
						{
							if( $k == $strVariableName )
							{
								$var = $v;
								break;
							}
							else
							{
								continue;
							}
						}
						
						if( $var != NULL )
						{
							// Go on.
							if( is_array( $var ) )
							{
								// Variable contains an array.
								// Iterate the array.
								for( $i = 0; $i < count($var); $i++ )
								{
									// The current iteration element.
									$element = $var[$i];
									
									// Handle different types of element.
									if( is_object( $element ) )
									{
										$strLoopIterationPart = $strLoopContent;
										
										// Create Reflection object to current element.
										$oReflectionObject = new ReflectionObject( $element );
										
										// Find all object calls.
										if( preg_match_all( "/\\$\{\b$strVariableName\b\}\{([A-Za-z0-9_]+)\}/" , 
												$strLoopContent , $matches , PREG_SET_ORDER ) != FALSE )
										{
											// Iterate the object calls.
											foreach( $matches as $match )
											{
												$property = $match[1];
												
												try
												{
													// Find the "get*" method of the $property.
													$oMethod=$oReflectionObject->getMethod("get".ucfirst($property));
													$retVal=$oMethod->invoke($element);
												}
												catch(Exception $e)
												{
													try
													{
														// Find the property named by $property.
														$oProperty=$oReflectionObject->getProperty($property);
														$retVal=$oProperty->getValue($element);
													}
													catch(Exception $e2)
													{
														throw $e2;
													}
												}
												
												// Build the pattern which is to replace with the retval.
												$strPattern = "\${".$strVariableName."}{".$property."}";
												
												// Replace.
												$strLoopIterationPart = str_replace( $strPattern , $retVal , $strLoopIterationPart );
											}
											
											$finalLoopContent = $finalLoopContent . $strLoopIterationPart;
											
										}
										else
										{
											throw new Exception( "Template error: There are no object calls." );
										}
									}
									//
									// DATATYPE: Scalar
									//
                  elseif( is_string( $element ) || is_int( $element ) || is_float( $element ) )
                  {
										$strLoopIterationPart = $strLoopContent;
										
										// Find all object calls.
										if( preg_match_all( "/\\$\{\b$strVariableName\b\}\{\\$\}/" , 
												$strLoopContent , $matches , PREG_SET_ORDER ) != FALSE )
										{
											// Iterate the object calls.
											foreach( $matches as $match )
											{
												// Build the pattern which is to replace with the retval.
												$strPattern = "\${".$strVariableName."}{\$}";
												
												// Replace.
												$strLoopIterationPart = str_replace( $strPattern , $element , $strLoopIterationPart );
											}
											
											$finalLoopContent = $finalLoopContent . $strLoopIterationPart;
											
										}
										else
										{
											throw new Exception( "Template error: There are no direct accessable value calls." );
										}
                  }
								}
							}
							else
							{
								throw new Exception( "Template error: Loop variable must be from type array." );
							}
						}
						else
						{
							// The required variable which is to iterate in a loop
							// doesn't exist in the replacements array.
							//throw new Exception( "Template error: Missing variable '$strVariableName'" );
							
							// Remove the loop from template.
							$finalLoopContent = "";
						}
					}
					else
					{
						throw new Exception( "Templates syntax error: Wrong variable name." );	
					}
					
					// Replace the templateContent's loop with the replaced one.
					$strLoopContent = substr(
							$this->m_templateContent , $iPosHead , ($iPosFoot+9)-$iPosHead
						);
						
					$this->m_templateContent = str_replace( $strLoopContent , $finalLoopContent , $this->m_templateContent );
					
					// Set the next start of loop search to the end of the loop-foot.
					$iOffset = $iPosHead;
				}
				else
				{
					// The foot of the loop missed.
					// Syntax error.
					throw new Exception( "Template syntax error: Missing foot of loop." );
				}
			}
		}
		while( $iPosHead );
	}
	
	/**
	 * Searches all include statements and reads the contents from the include
	 * files into the current template.
	 *
	 */
	private function doIncludes()
	{
		// The regexpression for the include-tags.
		$strRegex = "/\[\{INCLUDE ([^ \}]+)\}\]/";
		
		// Match the regex.
		if( preg_match_all( $strRegex , $this->m_templateContent , $matches , PREG_SET_ORDER ) )
		{
			// Iterate the matches, if there are any..
			foreach( $matches as $match )
			{
				//print( "Match: " . $match[0] . "<br />" );
				//print( "Match-Group: " . $match[1] . "<br />" );
				
				// Check whether the include-file exists.
				$oIncFile = new IF_File( $match[1] );
				
				if( $oIncFile->exists() )
				{
					// Read the contents of the file.
					$fileContent = file_get_contents( $oIncFile->getPath() );
					
					// Insert the content into the current template file.
					$this->m_templateContent = str_replace( $match[0] , $fileContent , $this->m_templateContent );
				}
				else
				{
					// The file which is to include can not be found.
					throw new Exception( "Template error: The file \"" . $match[1] . "\" doesn't exist." );
				}
			}
		}
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function processTemplate( $bLoops = TRUE , $bReplacements = TRUE , $bIncludes = TRUE )
	{
		// Print out the finished template string.
		print(self::getProcessedTemplate($bLoops, $bReplacements, $bIncludes));
	}

  public function getProcessedTemplate($bLoops=true, $bReplacements=true, $bIncludes=true)
  {
		// Find includes.
		self::doIncludes();

		// Find IFDEF tags.
		self::doDefines($this->m_templateContent);

		// Find loops.
		self::doLoops();

    // Replace translations.
    self::doTranslations($this->m_templateContent);

		// Replace variables.
		$this->m_templateContent = self::doReplacements( $this->m_templateContent , $this->m_replacements );
    return $this->m_templateContent;
  }
	
}
?>