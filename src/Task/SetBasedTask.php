<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * Parent Phing task with all general methods and properties.
 */
class SetBasedTask extends Task
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If set stop build on errors.
   *
   * @var bool
   */
  protected $myHaltOnError = true;

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
    // TODO: Implement main() method.
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute haltOnError.
   *
   * @param $theHaltOnError
   */
  public function setHaltOnError($theHaltOnError)
  {
    $this->myHaltOnError = (boolean)$theHaltOnError;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @throws BuildException
   */
  protected function logError()
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
  protected function logVerbose()
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
   */
  protected function logInfo()
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
}

//--------------------------------------------------------------------------------------------------------------------
