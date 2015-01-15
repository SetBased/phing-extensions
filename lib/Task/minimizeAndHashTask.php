<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Class minimizeAndHashTask
 */
class minimizeAndHashTask extends Task
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The (relative) path to the YUI compressor.
   *
   * @var string
   */
  private $myCompressorPath;

  /**
   * If set create gzip files from compressed include files.
   *
   * @var bool
   */
  private $myGzipFlag = false;

  /**
   * If set stop build on errors.
   *
   * @var bool
   */
  private $myHaltOnError = true;

  /**
   * The base dir of the include fileset.
   *
   * @var string
   */
  private $myIncludeBaseDir;

  /**
   * The names of the include files.
   *
   * @var array
   */
  private $myIncludeFileNames;

  /**
   * Info about include files.
   *
   * @var array
   */
  private $myIncludeFilesInfo;

  /**
   * The ID of the fileset with include files.
   *
   * @var string
   */
  private $myIncludes;

  /**
   * @var bool
   */
  private $myPreserveModificationPermissions = true;

  /**
   * @var bool
   */
  private $myPreserveModificationTime = false;

  /**
   * The phing project.
   *
   * @var Project
   */
  private $myProject;

  /**
   * Array with origin names of include files and new include files with hash.
   *
   * @var array
   */
  private $myReplacePairs;

  /**
   * The path to the resource dir (relative to the build dir).
   *
   * @var string
   */
  private $myResourceDir;

  /**
   * The absolute path to the resource dir.
   *
   * @var string
   */
  private $myResourceDirFullPath;

  /**
   * The names of the sources files.
   *
   * @var array
   */
  private $mySourceFileNames;

  /**
   * Info about source files.
   *
   * @var array
   */
  private $mySourceFilesInfo;

  /**
   * The ID of the fileset with include sources.
   *
   * @var string
   */
  private $mySources;

  /**
   * The base dir of the sources fileset.
   *
   * @var string
   */
  private $mySourcesBaseDir;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Initialises this task.
   */
  public function init()
  {
    $this->myProject = $this->getProject();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main method of this task.
   */
  public function main()
  {
    // Get base info about project.
    $this->prepareProjectData();

    // Get all info about source files.
    $this->getInfoSourceFiles();

    // Get all info about include files.
    $this->getInfoIncludeFiles();

    $this->prepareIncludeFiles();

    // Compress and rename files with hash.
    $this->processIncludeFiles();

    // Prepare all place holders.
    $this->preparePlaceHolders();

    // Replace resource file names with the file names of the compressed resources.
    $this->processingSourceFiles();

    // Compress file with gzip.
    if ($this->myGzipFlag) $this->gzipCompress();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute compressor_path.
   *
   * @param $theCompressorPath string The path to the YUI compressor
   */
  public function setCompressorPath( $theCompressorPath )
  {
    $this->myCompressorPath = $theCompressorPath;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute GZip.
   *
   * @param $theGzipFlag bool.
   */
  public function setGzip( $theGzipFlag = false )
  {
    $this->myGzipFlag = (boolean)$theGzipFlag;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute haltonerror.
   *
   * @param $theHaltOnError
   */
  public function setHaltOnError( $theHaltOnError )
  {
    $this->myHaltOnError = (boolean)$theHaltOnError;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute include.
   *
   * @param $theIncludes string The ID of the fileset with include sources.
   */
  public function setIncludes( $theIncludes )
  {
    $this->myIncludes = $theIncludes;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute preserveLastModified.
   *
   * @param $thePreserveLastModifiedFlag bool
   */
  public function setPreserveLastModified( $thePreserveLastModifiedFlag )
  {
    $this->myPreserveModificationTime = (boolean)$thePreserveLastModifiedFlag;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute preservePermissions.
   *
   * @param $thePreservePermissionsFlag bool
   */
  public function setPreservePermissions( $thePreservePermissionsFlag )
  {
    $this->myPreserveModificationPermissions = (boolean)$thePreservePermissionsFlag;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute base_dir.
   *
   * @param $theResourceDir string The path to the resource dir.
   */
  public function setResourceDir( $theResourceDir )
  {
    $this->myResourceDir = $theResourceDir;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute sources.
   *
   * @param $theSources string The ID of the fileset with include sources.
   */
  public function setSources( $theSources )
  {
    $this->mySources = $theSources;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check the possible duplicate files.
   * E.g. /js/jquery/jquery.min.js and /js/jquery/jquery-min.js
   *
   * @param $theFullPathName string The full path name of checking the file.
   *
   * @throws BuildException Throw build error if the duplicate files exist.
   */
  private function checkMultipleMinimizedFiles( $theFullPathName )
  {
    $path_parts = pathinfo( $theFullPathName );

    $postfix = substr( $path_parts['filename'], -4 );

    if ($postfix=='-min')
    {
      $filename       = substr( $path_parts['filename'], 0, (strlen( $path_parts['filename'] ) - 4) ).'.min';
      $full_path_name = $path_parts['dirname'].'/'.$filename.'.'.$path_parts['extension'];

      if ($this->myIncludeFilesInfo[$full_path_name]) $this->logError( "Found duplicate files '%s' and '%s'.",
                                                                       $theFullPathName,
                                                                       $full_path_name );
    }

    if ($postfix=='.min')
    {
      $filename       = substr( $path_parts['filename'], 0, (strlen( $path_parts['filename'] ) - 4) ).'-min';
      $full_path_name = $path_parts['dirname'].'/'.$filename.'.'.$path_parts['extension'];

      if ($this->myIncludeFilesInfo[$full_path_name]) $this->logError( "Found duplicate files '%s' and '%s'.",
                                                                       $theFullPathName,
                                                                       $full_path_name );
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the permissions of a file.
   *
   * @param string $theFilename The filename.
   *
   * @throws BuildException
   * @return int
   */
  private function getFilePermissions( $theFilename )
  {
    $perms = fileperms( $theFilename );
    if ($perms===false) $this->logError( "Unable to get permissions of file '%s'.", $theFilename );

    return $perms;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get hash of current file and make full path with hash to file and path name with hash in source files.
   *
   * @param $theIncludeFileInfo array  The file info for which need to get the hash.
   */
  private function getHashMinimizedFile( &$theIncludeFileInfo )
  {
    $file_content = file_get_contents( $theIncludeFileInfo['full_temp_name'] );

    if ($file_content===false) $this->logError( "Can not read the file '%s' or file does not exist.",
                                                $theIncludeFileInfo['full_temp_name'] );

    $theIncludeFileInfo['hash']                           = md5( $file_content );
    $theIncludeFileInfo['full_path_name_with_hash']       = $this->makePathWidthHash( $theIncludeFileInfo );
    $theIncludeFileInfo['path_name_in_sources_with_hash'] = $this->getPathInSources( $theIncludeFileInfo['full_path_name_with_hash'] );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an array with info about include files found in a source file.
   *
   * @param $theSourceFileContent string Content of updated source file.
   *
   * @return array
   */
  private function getIncludeFilesInSource( $theSourceFileContent )
  {
    $include_files = array();
    foreach ($this->myIncludeFilesInfo as $file_info)
    {
      if (strpos( $theSourceFileContent, $file_info['path_name_in_sources_with_hash'] )!==false)
      {
        $include_files[] = $file_info;
      }
    }

    return $include_files;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get the info about each file in the fileset.
   */
  private function getInfoIncludeFiles()
  {
    $this->logVerbose( 'Get include files info.' );

    foreach ($this->myIncludeFileNames as $filename)
    {
      clearstatcache();

      $path      = $this->myIncludeBaseDir.'/'.$filename;
      $full_path = realpath( $path );

      $this->myIncludeFilesInfo[$full_path] = array('filename_in_fileset'  => $filename,
                                                    'full_path_name'       => $full_path,
                                                    'full_temp_name'       => $full_path.'.tmp',
                                                    'path_name_in_sources' => $this->getPathInSources( $full_path ),
                                                    'mode'                 => $this->getFilePermissions( $full_path ));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *  Get full path for each file from source file list.
   */
  private function getInfoSourceFiles()
  {
    $this->logVerbose( 'Get source files info.' );

    foreach ($this->mySourceFileNames as $theFileName)
    {
      $this->mySourceFilesInfo[] = $this->mySourcesBaseDir.'/'.$theFileName;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the maximum mtime of a sources file and its include files.
   *
   * @param $theSourceFilename string The name of the source file.
   * @param $theContent        string The content of the source file with renamed include file names.
   *
   * @return int
   */
  private function getMaxModificationTime( $theSourceFilename, $theContent )
  {
    $times = array();

    $time = filemtime( $theSourceFilename );
    if ($time===false) $this->logError( "Unable to get mtime of file '%s'.", $theSourceFilename );
    $times[] = $time;

    $include_files_info = $this->getIncludeFilesInSource( $theContent );
    foreach ($include_files_info as $include_file_info)
    {
      $time = filemtime( $include_file_info['full_path_name_with_hash'] );
      if ($time===false) $this->logError( "Unable to get mtime for file '%s'.", $include_file_info );
      $times[] = $time;
    }

    return max( $times );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get file path name in source form full path name.
   *
   * @param $thePath string The full path name of the file.
   *
   * @throws BuildException
   * @return string Return name of the file which use for include file in source.
   */
  private function getPathInSources( $thePath )
  {
    if (strncmp( $thePath, $this->myResourceDirFullPath, strlen( $this->myResourceDirFullPath ) )!=0)
    {
      throw new BuildException( 'Internal error.' );
    }

    return substr( $thePath, strlen( $this->myResourceDirFullPath ) );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compresses minimized include files with gzip.
   */
  private function gzipCompress()
  {
    $this->logInfo( 'Gzip compressing files.' );

    foreach ($this->myIncludeFilesInfo as $file_info)
    {
      $this->logVerbose( "Gzip compressing file '%s' to '%s'.",
                         $file_info['full_path_name_with_hash'],
                         $file_info['full_path_name_with_hash'].'.gz' );

      // Get data from the file.
      $data = file_get_contents( $file_info['full_path_name_with_hash'] );
      if ($data===false)
      {
        $this->logError( "Can not read the file '%s' or file does not exist.",
                         $file_info['full_path_name_with_hash'] );
      }

      // Compress data with gzip
      $data = gzencode( $data, 9 );
      if ($data===false)
      {
        $this->logError( "Can not write the file '%s' or file does not exist.",
                         $file_info['full_path_name_with_hash'].'.gz' );
      }

      // Write data to the file.
      $status = file_put_contents( $file_info['full_path_name_with_hash'].'.gz', $data );
      if ($status===false)
      {
        $this->logError( "Unable to write to file '%s",
                         $file_info['full_path_name_with_hash'].'.gz' );
      }

      // If required preserve mtime.
      if ($this->myPreserveModificationTime)
      {
        $this->setModificationTime( $file_info['full_path_name_with_hash'], $file_info['full_path_name_with_hash'].'.gz' );
      }

      // If required preserve file permissions.
      if ($this->myPreserveModificationTime)
      {
        $this->setFilePermissions( $file_info['full_path_name_with_hash'].'.gz', $file_info['mode'] );
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check the file whether it is compressed or not.
   *
   * @param $theFullPathName string The full file name.
   *
   * @return bool Return true if the file is already minimized otherwise return false.
   */
  private function isMinimizedFilename( $theFullPathName )
  {
    $path_parts = pathinfo( $theFullPathName );

    $postfix = substr( $path_parts['filename'], -4 );

    if ($postfix=='-min' || $postfix=='.min')
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @throws BuildException
   */
  private function logError()
  {
    $args   = func_get_args();
    $format = array_shift( $args );

    foreach ($args as &$arg)
    {
      if (!is_scalar( $arg )) $arg = var_export( $arg, true );
    }

    if ($this->myHaltOnError) throw new BuildException( vsprintf( $format, $args ) );
    else $this->log( vsprintf( $format, $args ), Project::MSG_ERR );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   */
  private function logInfo()
  {
    $args   = func_get_args();
    $format = array_shift( $args );

    foreach ($args as &$arg)
    {
      if (!is_scalar( $arg )) $arg = var_export( $arg, true );
    }

    $this->log( vsprintf( $format, $args ), Project::MSG_INFO );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   */
  private function logVerbose()
  {
    $args   = func_get_args();
    $format = array_shift( $args );

    foreach ($args as &$arg)
    {
      if (!is_scalar( $arg )) $arg = var_export( $arg, true );
    }

    $this->log( vsprintf( $format, $args ), Project::MSG_VERBOSE );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the filename of a file which includes the MD5 sum of the file.
   * E.g. /js/jquery/jquery.js =>  /js/jquery/jquery-0707313a8cdc41572e84b403711e7c75.js
   *
   * @param array $theIncludeFileInfo
   *
   * @return string The filename with MD5 hash.
   */
  private function makePathWidthHash( $theIncludeFileInfo )
  {
    if ($theIncludeFileInfo['is_minimized'])
    {
      $path_parts = pathinfo( $this->removeMinFilename( $theIncludeFileInfo['full_path_name'] ) );
    }
    else
    {
      $path_parts = pathinfo( $theIncludeFileInfo['full_path_name'] );
    }

    return $path_parts['dirname'].'/'.$path_parts['filename'].'-'.$theIncludeFileInfo['hash'].'.'.$path_parts['extension'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compresses Javascript or CSS file with the YUI compressor.
   *
   * @param $theIncludeFileInfo array Info of the file that must be compressed.
   */
  private function minimizeIncludeFile( $theIncludeFileInfo )
  {
    $this->logInfo( "Minimizing '%s'.", $theIncludeFileInfo['full_path_name'] );

    $command = 'java -jar '.escapeshellarg( $this->myCompressorPath ).' '.
      escapeshellarg( $theIncludeFileInfo['full_path_name'] ).
      ' -o '.escapeshellarg( $theIncludeFileInfo['full_temp_name'] );

    exec( $command, $output, $return_var );

    if ($return_var!=0)
    {
      $msg = '';
      foreach ($output as $text)
      {
        $msg .= $text."\n";
      }

      $this->logError( "Failed to minimize file '%s' with messages:\n%s",
                       $theIncludeFileInfo['full_path_name'],
                       $msg );
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds all already minimized files and they not minimized alternative. Removes alternative files and info about
   * those files. Updates file info for minimized file with possible alternative filename in source files.
   */
  private function prepareIncludeFiles()
  {
    foreach ($this->myIncludeFilesInfo as &$file_info)
    {
      if ($this->isMinimizedFilename( $file_info['full_path_name'] ))
      {
        // Test for possible duplicate minimized files.
        $this->checkMultipleMinimizedFiles( $file_info['full_path_name'] );

        // Make name of possible alternative not minimized file.
        $alternative_full_path_name = $this->removeMinFilename( $file_info['full_path_name'] );

        // If alternative file exist remove the file and file info about it.
        if (isset($this->myIncludeFilesInfo[$alternative_full_path_name]))
        {
          unset($this->myIncludeFilesInfo[$alternative_full_path_name]);
          unlink( $alternative_full_path_name );
          $this->logInfo( "Removed '%s'.", $alternative_full_path_name );
        }

        // Update the info about minimized file.
        $file_info['is_minimized']                     = true;
        $file_info['path_name_in_sources_alternative'] = $this->removeMinFilename( $file_info['path_name_in_sources'] );
      }
      else
      {
        $file_info['is_minimized'] = false;
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prepares place holders with include file names and new file names with hash.
   */
  private function preparePlaceHolders()
  {
    $this->logVerbose( 'Prepare place holders.' );

    foreach ($this->myIncludeFilesInfo as $file_info)
    {
      $this->myReplacePairs["'".$file_info['path_name_in_sources']."'"] = "'".$file_info['path_name_in_sources_with_hash']."'";
      $this->myReplacePairs['"'.$file_info['path_name_in_sources'].'"'] = '"'.$file_info['path_name_in_sources_with_hash'].'"';

      if (isset($file_info['path_name_in_sources_alternative']))
      {
        $this->myReplacePairs["'".$file_info['path_name_in_sources_alternative']."'"] = "'".$file_info['path_name_in_sources_with_hash']."'";
        $this->myReplacePairs['"'.$file_info['path_name_in_sources_alternative'].'"'] = '"'.$file_info['path_name_in_sources_with_hash'].'"';
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Get names all include and source files and full path name of resource dir.
   */
  private function prepareProjectData()
  {
    $this->logVerbose( 'Get source and include file names.' );

    // Get file list form the project by fileset ID.
    $includes                 = $this->myProject->getReference( $this->myIncludes );
    $this->myIncludeFileNames = $includes->getDirectoryScanner( $this->myProject )->getIncludedFiles();

    // Get base name of include dir from project.
    $this->myIncludeBaseDir = $includes->getDir( $this->myProject );

    // Get file list form the project by fileset ID.
    $sources                 = $this->myProject->getReference( $this->mySources );
    $this->mySourceFileNames = $sources->getDirectoryScanner( $this->myProject )->getIncludedFiles();

    // Get base name of source dir from project.
    $this->mySourcesBaseDir = $sources->getDir( $this->myProject );

    // Get full path name of resource dir.
    $this->myResourceDirFullPath = realpath( $this->myIncludeBaseDir.'/'.$this->myResourceDir );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Minimizes the include files then rename with hash. Remove original files from the build dir.
   */
  private function processIncludeFiles()
  {
    foreach ($this->myIncludeFilesInfo as &$file_info)
    {
      if ($file_info['is_minimized'])
      {
        // If file is minimized copy it with temp name.
        $status = copy( $file_info['full_path_name'], $file_info['full_temp_name'] );

        if (!$status) $this->logError( "Can not rename file: '%s' to '%s'.",
                                       $file_info['full_path_name'],
                                       $file_info['full_temp_name'] );
      }
      else
      {
        // If file is not minimized compress and save it with temp name.
        $this->minimizeIncludeFile( $file_info );
      }

      // Get hash for the compressed file.
      $this->getHashMinimizedFile( $file_info );

      // Rename compressed temp file with hash.
      $this->renameIncludeFile( $file_info );

      // If required preserve mtime.
      if ($this->myPreserveModificationTime)
      {
        $this->setModificationTime( $file_info['full_path_name'], $file_info['full_path_name_with_hash'] );
      }

      // If required preserve file permissions.
      if ($this->myPreserveModificationTime)
      {
        $this->setFilePermissions( $file_info['full_path_name_with_hash'], $file_info['mode'] );
      }

      // Remove the original file.
      unlink( $file_info['full_path_name'] );
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Replaces in the sources files the include file names with minimized and hashed file names.
   */
  private function processingSourceFiles()
  {
    $this->logVerbose( 'Replace include files with new compressed files.' );

    foreach ($this->mySourceFilesInfo as $source_filename)
    {
      $content = file_get_contents( $source_filename );
      if ($content===false) $this->logError( "Unable to read file '%s'.", $source_filename );

      $new_content = strtr( $content, $this->myReplacePairs );

      if ($content!=$new_content)
      {
        $time = null;

        // If required determine the latest modification time of the source file and its include files.
        if ($this->myPreserveModificationTime)
        {
          $time = $this->getMaxModificationTime( $source_filename, $new_content );
        }

        // Write sources file with modified include file names.
        $status = file_put_contents( $source_filename, $new_content );
        if ($status===false) $this->logError( "Updating file '%s' failed.", $source_filename );
        $this->logInfo( "Updated file '%s'.", $source_filename );

        // If required set the mtime to the latest modification time of the source file and its include files.
        if ($this->myPreserveModificationTime)
        {
          $status = touch( $source_filename, $time );
          if ($status===false) $this->logError( "Unable to set mtime for file '%s'.", $source_filename );
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Remove part '-min' or '.min' form the name of file.
   *
   * @param $FullPathName string The full path file name.
   *
   * @throws BuildException
   * @return string Return the full file name without part '-min' or '.min'.
   */
  private function removeMinFilename( $FullPathName )
  {
    $path_parts = pathinfo( $FullPathName );

    $postfix = substr( $path_parts['filename'], -4 );

    if ($postfix=='-min' || $postfix=='.min')
    {
      $filename = substr( $path_parts['filename'], 0, (strlen( $path_parts['filename'] ) - 4) );
    }
    else
    {
      throw new BuildException( 'Internal error.' );
    }

    return $path_parts['dirname'].'/'.$filename.'.'.$path_parts['extension'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Rename the compressed file with hash.
   *
   * @param $theIncludeFileInfo array Info about current file.
   */
  private function renameIncludeFile( $theIncludeFileInfo )
  {
    $this->logVerbose( "Rename file '%s' to '%s'.",
                       $theIncludeFileInfo['full_temp_name'],
                       $theIncludeFileInfo['full_path_name_with_hash'] );

    $status = rename( $theIncludeFileInfo['full_temp_name'], $theIncludeFileInfo['full_path_name_with_hash'] );

    if (!$status) $this->logError( "Can not rename file: '%s' to '%s'.",
                                   $theIncludeFileInfo['full_temp_name'],
                                   $theIncludeFileInfo['full_path_name_with_hash'] );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the mode of a file.
   *
   * @param string $theFilename The filename.
   * @param string $theMode     The file mode bits.
   *
   * @throws BuildException
   */
  private function setFilePermissions( $theFilename, $theMode )
  {
    $status = chmod( $theFilename, $theMode );
    if ($status===false) $this->logError( "Unable to set permissions for file '%s'.", $theFilename );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Copy the mtime form the source file to the destination file.
   *
   * @param $theSourceFilename      string The full file name of source file.
   * @param $theDestinationFilename string The full file name of destination file.
   */
  private function setModificationTime( $theSourceFilename, $theDestinationFilename )
  {
    $time = filemtime( $theSourceFilename );
    if ($time===false) $this->logError( "Unable to get mtime of file '%s'.", $theSourceFilename );

    $status = touch( $theDestinationFilename, $time );
    if ($status===false)
    {
      $this->logError( "Unable to set mtime of file '%s' to mtime of '%s",
                       $theDestinationFilename,
                       $theSourceFilename );
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
