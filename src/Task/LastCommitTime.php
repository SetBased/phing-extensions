<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Class LastCommitTime
 */
class LastCommitTime extends Task
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
   * Last commit time.
   *
   * @var string
   */
  private $myLastCommitTime;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    $this->getLastCommitTime();

    $this->setFilesMtime();
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
   * Get last commit time.
   */
  private function getLastCommitTime()
  {
    $this->myLastCommitTime = exec('git show -s --format=%ct');
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
   * Set last commit time to all files in build directory.
   */
  private function setFilesMtime()
  {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myDir));
    foreach ($files as $full_path => $file)
    {
      if ($file->isFile())
      {
        $this->logVerbose("Setting mtime of file '%s'.", $full_path);

        $success = touch($full_path, $this->myLastCommitTime);
        if (!$success) $this->logError("Can not set mtime of file '%s'.", $full_path);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
