<?php
//----------------------------------------------------------------------------------------------------------------------
require_once 'SetBasedTask.php';

//----------------------------------------------------------------------------------------------------------------------
/**
 * Phing task for removing recursively empty directories.
 */
class removeEmptyDirsTask extends SetBasedTask
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
  private $myWorkDirName;

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
    $this->logInfo("Removing empty directories under '%s'.", $this->myWorkDirName);

    $this->myCount = 0;

    $empty = $this->removeEmptyDirs($this->myWorkDirName);
    if ($empty)
    {
      $this->removeDir($this->myWorkDirName);
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
    $this->myWorkDirName = $theDirName;
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
   * Removes a directory and does housekeeping.
   *
   * @param string $theDir The directory to be removed.
   */
  private function removeDir($theDir)
  {
    $this->logVerbose("Removing '%s'.", $theDir);

    $suc = rmdir($theDir);
    if ($suc===false) $this->logError("Unable to remove directory '%s'.", $theDir);

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
