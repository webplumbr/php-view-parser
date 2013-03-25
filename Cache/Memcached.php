<?php
namespace Cache;

class Memcached implements \Cache\Manager
{
  protected $driver;
  
  public function __construct()
  {
    $this->driver = new \Memcached;
    $status = $this->driver->addServer('localhost', 11211);
    if (!$status) {
      throw new \Exception('Unable to connect to Memcache Server');
    }
  }
  
  /*
   * $expiresTime (time in seconds relative to current time and should not exceed 30 days)
   * 
   */
  public function set($key, $value, $expiresTime)
  {
    $status = $this->driver->set($key, $value, $expiresTime);
    if ($status === false) {
      error_log(
         sprintf(
          '%s : %s - %s', 
          __METHOD__, 
          $this->driver->getResultCode(), 
          $this->driver->getResultMessage()
         ));
    }
  }
  
  public function update($key, $value, $expiresTime)
  {
    $this->driver->replace($key, $value, $expiresTime);
  }
  
  public function get($key)
  {
    return $this->driver->get($key);
  }
  
  public function delete($key)
  {
    $this->driver->delete($key);
  }
}
