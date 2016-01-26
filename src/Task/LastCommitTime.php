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
   * Get last commit time of each file in the GIT repository.
   */
  private function getLastCommitTime()
  {
    // Execute command for get list with file name and mtime from GIT log
    $command = 'git log --format=format:%ai --name-only';
    exec($command, $output, $return);
    if ($return!=0) $this->logError("Can not execute command '%s' in exec", $command);

    // Find latest mtime for each file from $output
    $commit_date = '';
    foreach ($output as $line)
    {
      if (strtotime($line)!==false)
      {
        $commit_date = strtotime($line);
      }
      else
      {
        if (!isset($this->myLastCommitTime[$line]) || $this->myLastCommitTime[$line]<$commit_date)
        {
          $this->myLastCommitTime[$line] = $commit_date;
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
   * Set last commit time to all files in build directory.
   */
  private function setFilesMtime()
  {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myDir,FilesystemIterator::UNIX_PATHS));
    foreach ($files as $full_path => $file)
    {
      if ($file->isFile())
      {
        $key = substr($full_path, strlen($this->myDir.'/'));
        if (isset($this->myLastCommitTime[$key]))
        {
          $this->logVerbose("Set mtime of '%s' to '%s'.", $full_path,date('Y-m-d H:i:s',$this->myLastCommitTime[$key]));
          $success = touch($full_path, $this->myLastCommitTime[$key]);
          if (!$success)
          {
            $this->logError("\nCan't touch file '%s'.\n", $full_path);
          }
        }
      }
    }
  }

  //----------------------------------------------------------------------------------------------------------------------

}

//--------------------------------------------------------------------------------------------------------------------

