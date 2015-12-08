<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for removing recursively empty directories.
 */
class removeEmptyDirsTask extends Task
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The number of empty directories removed.
   *
   * @var int
   */
  private $myCount;

  /**
   * The parent directory under which all empty directories must be removed.
   *
   * @var string
   */
  private $myDirName;

  /**
   * If set stop build on errors.
   *
   * @var bool
   */
  private $myHaltOnError = true;

  /**
   * If set the parent directory must be removed too (if empty).
   *
   * @var bool
   */
  private $myRemoveParent = false;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    $this->logInfo("Removing empty directories under '%s'.", $this->myDirName);

    $this->myCount = 0;

    $empty = $this->removeEmptyDirs($this->myDirName);
    if ($empty)
    {
      $this->removeDir($this->myDirName);
    }

    $this->logInfo("Removed %d empty directories.", $this->myCount);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute dir.
   *
   * @param string $theDirName
   */
  public function setDir($theDirName)
  {
    $this->myDirName = $theDirName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute haltOnError.
   *
   * @param bool $theHaltOnError
   */
  public function setHaltOnError($theHaltOnError)
  {
    $this->myHaltOnError = (boolean)$theHaltOnError;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute removeParent.
   *
   * @param bool $theRemoveParent
   */
  public function setRemoveParent($theRemoveParent)
  {
    $this->myRemoveParent = (boolean)$theRemoveParent;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints an error message and depending on HaltOnError throws an exception.
   *
   * @param mixed ...$param The arguments as for [sprintf](http://php.net/manual/function.sprintf.php)
   *
   * @throws \BuildException
   */
  private function logError()
  {
    $args   = func_get_args();
    $format = array_shift($args);

    foreach ($args as &$arg)
    {
      if (!is_scalar($arg)) $arg = var_export($arg, true);
    }

    if ($this->myHaltOnError) throw new BuildException(vsprintf($format, $args));
    else $this->log(vsprintf($format, $args), Project::MSG_ERR);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints an info message.
   *
   * @param mixed ...$param The arguments as for [sprintf](http://php.net/manual/function.sprintf.php)
   */
  private function logInfo()
  {
    $args   = func_get_args();
    $format = array_shift($args);

    foreach ($args as &$arg)
    {
      if (!is_scalar($arg)) $arg = var_export($arg, true);
    }

    $this->log(vsprintf($format, $args), Project::MSG_INFO);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prints a verbose level message.
   *
   * @param mixed ...$param The arguments as for [sprintf](http://php.net/manual/function.sprintf.php)
   */
  private function logVerbose()
  {
    $args   = func_get_args();
    $format = array_shift($args);

    foreach ($args as &$arg)
    {
      if (!is_scalar($arg)) $arg = var_export($arg, true);
    }

    $this->log(vsprintf($format, $args), Project::MSG_VERBOSE);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes a directory and does housekeeping.
   *
   * @param string $theDir The directory to be removed.
   */
  private function removeDir($theDir)
  {
    $this->logInfo("Removing '%s'.", $theDir);

    $suc = rmdir($theDir);
    if ($suc===false) $this->logVerbose("Unable to remove directory '%s'.", $theDir);

    $this->myCount++;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes recursively empty directories under a parent directory.
   *
   * @param string $theDirName The parent directory.
   *
   * @return bool True if the parent directory is empty. Otherwise false.
   * @throws BuildException
   */
  private function removeEmptyDirs($theDirName)
  {
    $entries = scandir($theDirName, SCANDIR_SORT_ASCENDING);
    if ($entries===false) $this->logError("Unable to scan directory '%s'.", $theDirName);

    foreach ($entries as $i => $entry)
    {
      $path = $theDirName.'/'.$entry;

      if ($entry=='.' || $entry=='..')
      {
        unset($entries[$i]);
      }
      elseif (is_dir($path))
      {
        $empty = $this->removeEmptyDirs($path);
        if ($empty)
        {
          unset($entries[$i]);

          $this->removeDir($path);
        }
      }
    }

    return empty($entries);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
