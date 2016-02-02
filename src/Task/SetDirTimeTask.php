<?php
//----------------------------------------------------------------------------------------------------------------------
require_once 'SetBasedTask.php';

//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for setting recursively the mtime of a directories to the max mtime of its entries.
 */
class SetDirTimeTask extends SetBasedTask
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The parent directory under which the mtime of (source) files must be set.
   *
   * @var string
   */
  protected $myWorkDirName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myWorkDirName,
                                                                            FilesystemIterator::SKIP_DOTS),
                                             RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($iterator as $path => $file_info)
    {
      if ($file_info->isDir())
      {
        $mtime = $this->getMaxMTime($path);
        if (isset($mtime))
        {
          $this->logVerbose("Set mtime of '%s' to '%s'.", $path, date('Y-m-d H:i:s', $mtime));
          $success = touch($path, $mtime);
          if (!$success)
          {
            $this->logError("Unable to set mtime of '%s'.", $path);
          }
        }
      }
    }
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
   * Get max mtime from all files in directory.
   *
   * @param string $theDirName The name of the directory.
   *
   * @return int
   */
  private function getMaxMTime($theDirName)
  {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($theDirName, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST);

    $mtime = null;
    foreach ($iterator as $file_info)
    {
      $mtime = max($mtime, $file_info->getMTime());
    }

    return $mtime;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

