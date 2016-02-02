<?php
//----------------------------------------------------------------------------------------------------------------------
require_once 'SetBasedTask.php';

//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for reading a Semantic Version from the standard input.
 */
class ReadSemanticVersionTask extends SetBasedTask
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The filename with contain semantic version number.
   *
   * @var string
   */
  private $myFilename;

  /**
   * Name of variable in a build for major part of version number.
   *
   * @var string
   */
  private $myMajorProperty;

  /**
   * Name of variable in a build for minor part of version number.
   *
   * @var string
   */
  private $myMinorProperty;

  /**
   * Array with parts of new version number.
   *
   * @var array
   */
  private $myNewVersion = [];

  /**
   * Name of variable in a build for patch part of version number.
   *
   * @var string
   */
  private $myPatchProperty;

  /**
   * Name of variable in a build for pre-release part of version number (i.e. the part after - (if any)).
   *
   * @var string
   */
  private $myPreReleaseProperty;

  /**
   * Array with parts of previous version number.
   *
   * @var array
   */
  private $myPreviousVersion = [];

  /**
   * Name of variable in a build for release part of version number (i.e. MAJOR.MINOR.PATCH).
   *
   * @var string
   */
  private $myReleaseProperty;

  /**
   * Name of variable in a build for full version number.
   *
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
  public function setFile($theFile)
  {
    $this->myFilename = $theFile;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute majorProperty.
   *
   * @param string $theMajorVersion
   */
  public function setMajorProperty($theMajorVersion)
  {
    $this->myMajorProperty = $theMajorVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute minorProperty.
   *
   * @param string $theMinorVersion
   */
  public function setMinorProperty($theMinorVersion)
  {
    $this->myMinorProperty = $theMinorVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute patchProperty.
   *
   * @param string $thePatchVersion
   */
  public function setPatchProperty($thePatchVersion)
  {
    $this->myPatchProperty = $thePatchVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute preReleaseProperty.
   *
   * @param string $thePreReleaseVersion
   */
  public function setPreReleaseProperty($thePreReleaseVersion)
  {
    $this->myPreReleaseProperty = $thePreReleaseVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute releaseProperty.
   *
   * @param string $theReleaseVersion
   */
  public function setReleaseProperty($theReleaseVersion)
  {
    $this->myReleaseProperty = $theReleaseVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute versionProperty.
   *
   * @param string $theVersion
   */
  public function setVersionProperty($theVersion)
  {
    $this->myVersionProperty = $theVersion;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads previous version from file if filename is set.
   */
  private function readPreviousVersionNumber()
  {
    if ($this->myFilename)
    {
      if (is_file($this->myFilename))
      {
        $content = file_get_contents($this->myFilename);
        if ($content===false)
        {
          $this->logError("Not readable file '%s'.", $this->myFilename);
        }

        if ($content)
        {
          $this->myPreviousVersion = $this->validateSemanticVersion($content);
          if ($this->myPreviousVersion)
          {
            $this->logInfo("Current version is '%s'.", $this->myPreviousVersion['version']);
          }
          else
          {
            $this->logError("Version is '%s' is not a valid Semantic Version.",
                            $this->myPreviousVersion['version']);
          }
        }
      }
      else
      {
        $this->logInfo("File '%s' does not exist.", $this->myFilename);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Read new version number from php://stdin stream i.e CLI.
   */
  private function setNewVersionNumber()
  {
    $valid = false;
    while (!$valid)
    {
      echo "Enter new Semantic Version: ";

      $line               = fgets(STDIN);
      $this->myNewVersion = $this->validateSemanticVersion($line);
      $valid              = ($this->myNewVersion);

      if (!$valid)
      {
        $this->logInfo("'%s' is not a valid Semantic Version.", trim($line, "\n"));
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new properties to phing project with data about version number.
   */
  private function setProjectVersionProperties()
  {
    if ($this->myVersionProperty)
    {
      $this->project->setProperty($this->myVersionProperty, $this->myNewVersion['version']);
    }

    if ($this->myReleaseProperty)
    {
      $this->project->setProperty($this->myReleaseProperty, $this->myNewVersion['release']);
    }
    if ($this->myPreReleaseProperty)
    {
      $this->project->setProperty($this->myPreReleaseProperty, $this->myNewVersion['pre-release']);
    }


    if ($this->myMajorProperty)
    {
      $this->project->setProperty($this->myMajorProperty, $this->myNewVersion['major']);
    }
    if ($this->myMinorProperty)
    {
      $this->project->setProperty($this->myMinorProperty, $this->myNewVersion['minor']);
    }
    if ($this->myPatchProperty)
    {
      $this->project->setProperty($this->myPatchProperty, $this->myNewVersion['patch']);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes new version number into file if the filename is set.
   */
  private function updateVersionInFile()
  {
    if ($this->myFilename)
    {
      $status = file_put_contents($this->myFilename, $this->myNewVersion['version']);
      if (!$status)
      {
        $this->logError("File '%s' is not writable.", $this->myFilename);
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
   * @return array
   */
  private function validateSemanticVersion($theVersion)
  {
    /**
     * Notice:
     * Version validation http://semver.org/
     * Example:
     * Valid version numbers: 1, 1.2, 2.2.3, 1.2.6-alpha, 4.2.3-alpha.beta, 1.5.0-rc.1;
     * Invalid version numbers: 1., 1.2., 1beta, 4.5alpha, 1.2.3-rc_1;
     */
    $status = preg_match('/^(\d+)(?:\.(\d+))?(?:\.((\d+)(?:-([A-Za-z]+)(?:\.(\w+))?)?))?$/', $theVersion, $matches);

    $version = [];

    if ($status)
    {
      $version['version'] = $matches[0];
      $version['major']   = $matches[1];
      $version['minor']   = $matches[2];

      // The above regexp will put the pre-release part in the patch part. Separate patch and pre-release part using
      // ordinary string manipulation.
      $pos = strpos($matches[3], '-');
      if ($pos!==false)
      {
        $version['patch']       = substr($matches[3], 0, $pos);
        $version['pre-release'] = substr($matches[3], $pos + 1);
      }
      else
      {
        $version['patch']       = $matches[3];
        $version['pre-release'] = '';
      }

      $version['release'] = $version['major'].'.'.$version['minor'].'.'.$version['patch'];
    }

    return $version;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
