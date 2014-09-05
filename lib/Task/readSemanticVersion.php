<?php

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class readSemanticVersion
 */
class readSemanticVersion extends Task
{
  /**
   * @var string
   */
  private $myFilename;

  /**
   * @var bool
   */
  private $myHaltOnError;

  /**
   * @var string
   */
  private $myMajorProperty;

  /**
   * @var string
   */
  private $myMinorProperty;

  /**
   * @var array
   */
  private $myNewVersion = array();

  /**
   * @var string
   */
  private $myPatchProperty;

  /**
   * @var array
   */
  private $myPreviousVersion = array();

  /**
   * @var string
   */
  private $myVersionProperty;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    // Read current version form file.
    $this->readVersion();

    // Set new version from CLI.
    $this->setNewVersion();

    // Update version in file.
    $this->updateVersionInFile();

    // Set version properties for project.
    $this->setProjectVersionProperties();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute file.
   *
   * @param string $theFile
   */
  public function setFile( $theFile )
  {
    $this->myFilename = $theFile;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute haltonerror.
   *
   * @param bool $theHaltOnError
   */
  public function setHaltOnError( $theHaltOnError )
  {
    $this->myHaltOnError = (boolean)$theHaltOnError;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute majorProperty.
   *
   * @param string $theMajorVersion
   */
  public function setMajorProperty( $theMajorVersion )
  {
    $this->myMajorProperty = $theMajorVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute minorProperty.
   *
   * @param string $theMinorVersion
   */
  public function setMinorProperty( $theMinorVersion )
  {
    $this->myMinorProperty = $theMinorVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute patchProperty.
   *
   * @param string $theMajorVersion
   */
  public function setPatchProperty( $theMajorVersion )
  {
    $this->myPatchProperty = $theMajorVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute versionProperty.
   *
   * @param string $theMajorVersion
   */
  public function setVersionProperty( $theMajorVersion )
  {
    $this->myVersionProperty = $theMajorVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @throws BuildException
   */
  private function logError()
  {
    $args   = func_get_args();
    $format = array_shift( $args );

    foreach ($args as &$arg)
    {
      if (!is_scalar( $arg )) $arg = var_export( $arg, true );
    }

    if ($this->myHaltOnError) throw new BuildException(vsprintf( $format, $args ));
    else $this->log( vsprintf( $format, $args ), Project::MSG_ERR );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   */
  private function logInfo()
  {
    $args   = func_get_args();
    $format = array_shift( $args );

    foreach ($args as &$arg)
    {
      if (!is_scalar( $arg )) $arg = var_export( $arg, true );
    }

    $this->log( vsprintf( $format, $args ), Project::MSG_INFO );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Read previous version number from file with build version number if the filename is set.
   */
  private function readVersion()
  {
    if ($this->myFilename)
    {
      $content = file_get_contents( $this->myFilename );
      if ($content===false)
      {
        $this->logError( "Not readable file '%s or file does not exist." );
      }

      if ($content)
      {
        $this->myPreviousVersion = $this->validateSemanticVersion( $content );
        $this->logInfo( "Current version is '%s'.", $this->myPreviousVersion['version'] );
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Read new version number from php://stdim stream i.e CLI.
   */
  private function setNewVersion()
  {
    echo "Enter new version: ";

    $line = fgets( STDIN );

    $this->myNewVersion = $this->validateSemanticVersion( $line );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add new properties to phing project with data about version number.
   */
  private function setProjectVersionProperties()
  {
    // Add new version number.
    $this->project->setNewProperty( $this->myVersionProperty, $this->myNewVersion['version'] );
    $this->project->setNewProperty( $this->myMajorProperty, $this->myNewVersion['major'] );
    $this->project->setNewProperty( $this->myMinorProperty, $this->myNewVersion['minor'] );
    $this->project->setNewProperty( $this->myPatchProperty, $this->myNewVersion['patch'] );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Write new version number into file if the filename is set.
   */
  private function updateVersionInFile()
  {
    if ($this->myFilename)
    {
      $status = file_put_contents( $this->myFilename, $this->myNewVersion['version'] );

      if (!$status)
      {
        $this->logError( "File '%s' is not writable.", $this->myFilename );
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Validates a string is a valid Semantic Version. If the string is semantic version returns an array with the parts
   * of the semantic version. Otherwise returns null.
   *
   * @param string $theVersion The string the be validated.
   *
   * @return array|null
   */
  private function validateSemanticVersion( $theVersion )
  {
    if (count( $this->myPreviousVersion )!=0)
    {
      $version = $this->myPreviousVersion;
    }
    else
    {
      $version = array('version' => '0.0.0',
                       'major'   => 0,
                       'minor'   => 0,
                       'patch'   => 0);
    }

    $status = preg_match( '/^(\d+)\.(\d+)\.(\d+)$/', $theVersion, $matches );

    if ($status)
    {
      $version['version'] = $matches[0];
      $version['major']   = $matches[1];
      $version['minor']   = $matches[2];
      $version['patch']   = $matches[3];
    }
    else
    {
      $this->logError( "Invalid version number '%s'.", trim( $theVersion, "\n" ) );
    }

    return $version;
  }

  //--------------------------------------------------------------------------------------------------------------------

}
//----------------------------------------------------------------------------------------------------------------------
