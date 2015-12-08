<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Class LastCommitTime
 */
class LastCommitTime extends Task
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Param for output in console.
   *
   * @var bool
   */
  private $myVerbose;

  /**
   * Build dir.
   *
   * @var string
   */
  private $myDir;

  /**
   * Array with last commit time for each file.
   *
   * @var array
   */
  private $myLastCommitTime = [];

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
   * Setter for XML attribute verbose.
   *
   * @param $theVerbose
   */
  public function setVerbose($theVerbose)
  {
    $this->myVerbose = $theVerbose;
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
   *  Called by the project to let the task do it's work. This method may be
   *  called more than once, if the task is invoked more than once. For
   *  example, if target1 and target2 both depend on target3, then running
   *  <em>phing target1 target2</em> will run all tasks in target3 twice.
   *
   *  Should throw a BuildException if someting goes wrong with the build
   *
   *  This is here. Must be overloaded by real tasks.
   */
  public function main()
  {
    $this->getLastCommitTime();

    $this->setFilesMtime();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get last commit time.
   */
  private function getLastCommitTime()
  {
    exec('git log --format=format:%ai --name-only .|cat', $output);
    $commit_date = '';
    foreach ($output as $line)
    {
      if (strtotime($line)!==false)
      {
        $commit_date = strtotime($line);
      }
      else
      {
        if ($this->myLastCommitTime[$line]<$commit_date)
        {
          $this->myLastCommitTime[$line] = $commit_date;
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Set last commit time to all files in build directory.
   */
  private function setFilesMtime()
  {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myDir));
    foreach ($files as $fullpath => $file)
    {
      if ($file->isFile())
      {
        $key = str_replace($this->myDir.'/', '', $fullpath);
        if (array_key_exists($key, $this->myLastCommitTime))
        {
          if ($this->myVerbose)
          {
            $this->logInfo("Set new mtime '%s'.", $fullpath);
          }
          if (!touch($fullpath, $this->myLastCommitTime[$key]))
          {
            throw new \SetBased\Abc\Error\RuntimeException("\nCan't touch file '%s'.\n", $fullpath);
          }
        }
      }
    }
  }
  //--------------------------------------------------------------------------------------------------------------------

}

//--------------------------------------------------------------------------------------------------------------------
