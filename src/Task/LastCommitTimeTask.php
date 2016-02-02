<?php
//----------------------------------------------------------------------------------------------------------------------
require_once 'SetBasedTask.php';

//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for set the mtime of (source) file to the latest commit time in Git.
 */
class LastCommitTimeTask extends SetBasedTask
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The number of sources files found.
   *
   * @var int
   */
  private $myCount = 0;

  /**
   * Array with last commit time for each file.
   *
   * @var array
   */
  private $myLastCommitTime = [];

  /**
   * The parent directory under which the mtime of (source) files must be set.
   *
   * @var string
   */
  private $myWorkDirName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    $this->logInfo("Preserving last commit time under directory %s", $this->myWorkDirName);

    $this->getLastCommitTime();

    $this->setFilesMtime();

    $this->logInfo("Found %d files", $this->myCount);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute dir.
   *
   * @param string $theWorkDirName The name of the working directory.
   */
  public function setDir($theWorkDirName)
  {
    $this->myWorkDirName = $theWorkDirName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get last commit time of each file in the Git repository.
   */
  private function getLastCommitTime()
  {
    // Execute command for get list with file name and mtime from GIT log
    $command = "git log --format='format:%ai' --name-only";
    exec($command, $output, $return);
    if ($return!=0) $this->logError("Can not execute command %s in exec", $command);

    // Find latest mtime for each file from $output.
    // Note: Each line is either:
    //       * an empty line
    //       * a timestamp
    //       * a filename.
    $commit_date = '';
    foreach ($output as $line)
    {
      if ((preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} [+\-]\d{4}$/', $line)))
      {
        $commit_date = strtotime($line);
      }
      else if ($line!=='')
      {
        $file_name = $line;
        if (!isset($this->myLastCommitTime[$file_name]) || $this->myLastCommitTime[$file_name]<$commit_date)
        {
          $this->myLastCommitTime[$file_name] = $commit_date;
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
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myWorkDirName,
                                                                          FilesystemIterator::UNIX_PATHS));
    foreach ($files as $full_path => $file)
    {
      if ($file->isFile())
      {
        $key = substr($full_path, strlen($this->myWorkDirName.'/'));
        if (isset($this->myLastCommitTime[$key]))
        {
          $this->logVerbose("Set mtime of %s to %s", $full_path, date('Y-m-d H:i:s', $this->myLastCommitTime[$key]));
          $success = touch($full_path, $this->myLastCommitTime[$key]);
          if (!$success)
          {
            $this->logError("Unable to set mtime of file %s", $full_path);
          }

          $this->myCount++;
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
