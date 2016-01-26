<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Class LastCommitTime
 */
class SetDirTime extends Task
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Build dir.
   *
   * @var string
   */
  private $myDir;

  /**
   * If set stop build on errors.
   *
   * @var bool
   */
  private $myHaltOnError = true;

  /**
   * Array with last commit time for each file.
   *
   * @var array
   */
  private $myLastCommitTime = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    $this->setDirMtime();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute dir.
   *
   * @param $theDir
   */
  public function setDir($theDir)
  {
    $this->myDir = $theDir;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute haltonerror.
   *
   * @param $theHaltOnError
   */
  public function setHaltOnError($theHaltOnError)
  {
    $this->myHaltOnError = (boolean)$theHaltOnError;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get last commit time of each file in the GIT repository.
   */
  private function setDirMtime()
  {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myDir, FilesystemIterator::SKIP_DOTS),
                                             RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($objects as $path => $object)
    {
      if ($object->isDir())
      {
        $mtime = $this->getLastMTime($path);
        if ($mtime==0) $mtime = $object->getMTime();

        $this->logVerbose("Set mtime of '%s' to '%s'.", $path, date('Y-m-d H:i:s', $mtime));
        $success = touch($path, $mtime);
        if (!$success)
        {
          $this->logError("\nCan't touch dir '%s'.\n", $path);
        }
      }
    }
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
   * Print in console
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
   * Print in console
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
   * Get last commit time from all files in directory.
   *
   * @param $theDir
   *
   * @return mixed
   *
   * @throws BuildException
   */
  private function getLastMTime($theDir)
  {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theDir, FilesystemIterator::SKIP_DOTS),
                                             RecursiveIteratorIterator::SELF_FIRST);

    $mtime = [];
    foreach ($objects as $object)
    {
      $mtime[] = $object->getMTime();
    }

    return (count($mtime)>0) ? max($mtime) : 0;
  }

  //----------------------------------------------------------------------------------------------------------------------

}

//--------------------------------------------------------------------------------------------------------------------

