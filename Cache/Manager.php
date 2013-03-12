<?php
namespace Cache;

interface Manager
{
  public function set($key, $value, $expiresTime);
  public function get($key);
  public function delete($key);
  public function update($key, $value, $expiresTime);
}