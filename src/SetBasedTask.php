<?php
declare(strict_types=1);

namespace SetBased\Phing\Task;

/**
 * Parent Phing task with all general methods and properties.
 */
abstract class SetBasedTask extends \Task
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If set stop build on errors.
   *
   * @var bool
   */
  protected $haltOnError = true;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for XML attribute haltOnError.
   *
   * @param bool $haltOnError If set stop build on errors.
   */
  public function setHaltOnError(bool $haltOnError): void
  {
    $this->haltOnError = $haltOnError;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If $myHaltOnError is set throws a BuildException with, otherwise creates a log event with priority
   * Project::MSG_ERR.
   *
   * @param mixed ...$param The format and arguments similar as for
   *                        [sprintf](http://php.net/manual/function.sprintf.php)
   *
   * @throws \BuildException
   */
  protected function logError(): void
  {
    $args   = func_get_args();
    $format = array_shift($args);

    foreach ($args as &$arg)
    {
      if (!is_scalar($arg)) $arg = var_export($arg, true);
    }

    if ($this->haltOnError) throw new \BuildException(vsprintf($format, $args));
    else $this->log(vsprintf($format, $args), \Project::MSG_ERR);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a log event with priority Project::MSG_INFO.
   *
   * @param mixed ...$param The format and arguments similar as for
   *                        [sprintf](http://php.net/manual/function.sprintf.php)
   */
  protected function logInfo(): void
  {
    $args   = func_get_args();
    $format = array_shift($args);

    foreach ($args as &$arg)
    {
      if (!is_scalar($arg)) $arg = var_export($arg, true);
    }

    $this->log(vsprintf($format, $args), \Project::MSG_INFO);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a log event with priority Project::MSG_VERBOSE.
   *
   * @param mixed ...$param The format and arguments similar as for
   *                        [sprintf](http://php.net/manual/function.sprintf.php)   *
   *
   */
  protected function logVerbose(): void
  {
    $args   = func_get_args();
    $format = array_shift($args);

    foreach ($args as &$arg)
    {
      if (!is_scalar($arg)) $arg = var_export($arg, true);
    }

    $this->log(vsprintf($format, $args), \Project::MSG_VERBOSE);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
