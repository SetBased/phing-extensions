<?php
//----------------------------------------------------------------------------------------------------------------------
require_once 'MTime.php';

//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for setting recursively the mtime of a directory the the max mtime of its entries.
 */
class SetDirTime extends MTime
{
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

    $mtime = null;
    foreach ($objects as $object)
    {
      $mtime = max($mtime, $object->getMTime());
    }

    return $mtime;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get last commit time of each file in the GIT repository.
   */
  private function setDirMtime()
  {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->myDir,
                                                                            FilesystemIterator::SKIP_DOTS),
                                             RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($objects as $path => $object)
    {
      if ($object->isDir())
      {
        $mtime = $this->getLastMTime($path);
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
}

//----------------------------------------------------------------------------------------------------------------------

