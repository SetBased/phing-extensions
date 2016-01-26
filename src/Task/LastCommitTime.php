<?php
//----------------------------------------------------------------------------------------------------------------------
require_once 'MTime.php';

//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for set the mtime of (source) file to the latest commit in GIT.
 */
class LastCommitTime extends MTime
{
  //--------------------------------------------------------------------------------------------------------------------
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
   * Set last commit time to all files in build directory.
   */
  private function setFilesMtime()
  {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myDir,
                                                                          FilesystemIterator::UNIX_PATHS));
    foreach ($files as $full_path => $file)
    {
      if ($file->isFile())
      {
        $key = substr($full_path, strlen($this->myDir.'/'));
        if (isset($this->myLastCommitTime[$key]))
        {
          $this->logVerbose("Set mtime of '%s' to %s", $full_path, date('Y-m-d H:i:s', $this->myLastCommitTime[$key]));
          $success = touch($full_path, $this->myLastCommitTime[$key]);
          if (!$success)
          {
            $this->logError("Unable to set mtime of file '%s'.", $full_path);
          }
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

