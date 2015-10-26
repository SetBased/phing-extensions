<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for removing recursively empty directories.
 */
class removeEmptyDirsTask extends Task
{
  //--------------------------------------------------------------------------------------------------------------------
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

    $empty = $this->removeEmptyDirs($this->myDirName);
    if ($empty)
    {
      $suc = rmdir($this->myDirName);
      if ($suc===false) $this->logError("Unable to remove directory '%s'.", $this->myDirName);
    }
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
   * @throws BuildException
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

          $this->logInfo("Removing '%s'.", $path);

          $suc = rmdir($path);
          if ($suc===false) $this->logError("Unable to remove directory '%s'.", $path);
        }
      }
    }

    return empty($entries);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
