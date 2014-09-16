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
    $this->readPreviousVersionNumber();

    // Set new version from CLI.
    $this->setNewVersionNumber();

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
   * @param string $theVersion
   */
  public function setVersionProperty( $theVersion )
  {
    $this->myVersionProperty = $theVersion;
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
   * Reads previous version from file if filename is set.
   */
  private function readPreviousVersionNumber()
  {
    if ($this->myFilename)
    {
      if (is_file( $this->myFilename ))
      {
        $content = file_get_contents( $this->myFilename );
        if ($content===false)
        {
          $this->logError( "Not readable file '%s'.", $this->myFilename );
        }

        if ($content)
        {
          $this->myPreviousVersion = $this->validateSemanticVersion( $content );
          if ($this->myPreviousVersion)
          {
            $this->logInfo( "Current version is '%s'.", $this->myPreviousVersion['version'] );
          }
          else
          {
            $this->logError( "Version is '%s' is not a valid Semantic Version.",
                             $this->myPreviousVersion['version'] );
          }
        }
      }
      else
      {
        $this->logInfo( "File '%s' does not exist.", $this->myFilename );
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Read new version number from php://stdin stream i.e CLI.
   */
  private function setNewVersionNumber()
  {
    echo "Enter new version: ";

    $line = fgets( STDIN );

    $this->myNewVersion = $this->validateSemanticVersion( $line );

    if (!$this->myNewVersion)
    {
      // If new version not valid. Exit. xxx
      $this->myHaltOnError = true;
      $this->logError( "Set invalid version number '%s'.", trim( $line, "\n" ) );
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new properties to phing project with data about version number.
   */
  private function setProjectVersionProperties()
  {
    $this->project->setNewProperty( $this->myVersionProperty, $this->myNewVersion['version'] );
    $this->project->setNewProperty( $this->myMajorProperty, $this->myNewVersion['major'] );
    $this->project->setNewProperty( $this->myMinorProperty, $this->myNewVersion['minor'] );
    $this->project->setNewProperty( $this->myPatchProperty, $this->myNewVersion['patch'] );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes new version number into file if the filename is set.
   */
  private function updateVersionInFile()
  {
    if ($this->myFilename)
    {
      // trim version if last part is 0
      if (!($this->myNewVersion['patch']) && !($this->myNewVersion['minor']))
      {
        $version = $this->myNewVersion['major'];
      }
      elseif (!($this->myNewVersion['patch']))
      {
        $version = $this->myNewVersion['major'].'.'.$this->myNewVersion['minor'];
      }
      else
      {
        $version = $this->myNewVersion['major'].'.'.$this->myNewVersion['minor'].'.'.$this->myNewVersion['patch'];
      }

      $status = file_put_contents( $this->myFilename, $version );
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
    /**
     * Notice:
     * Version validation http://semver.org/
     * Example:
     * Valid version numbers: 1, 1.2, 2.2.3, 1.2.6-alpha, 4.2.3-alpha.beta, 1.5.0-rc.1;
     * Invalid version numbers: 1., 1.2., 1beta, 4.5alpha, 1.2.3-rc_1;
     */
    $status = preg_match( '/^(\d+)(?:\.(\d+))?(?:\.((\d+)(?:-([A-Za-z]+)(?:\.(\w+))?)?))?$/', $theVersion, $matches );

    $version = array();

    if ($status)
    {
      $version['version'] = $matches[0];
      $version['major']   = $matches[1];
      $version['minor']   = $matches[2];
      $version['patch']   = $matches[3];
      $version['patch_x'] = $matches[4];
      $version['patch_y'] = $matches[5];
      $version['patch_z'] = $matches[6];
    }

    return $version;
  }

  //--------------------------------------------------------------------------------------------------------------------

}
//----------------------------------------------------------------------------------------------------------------------
