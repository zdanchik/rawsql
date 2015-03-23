<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 23.03.2015
 * Time: 12:54
 */

namespace RawSql;

use Sonata\Cache\Adapter\Cache\MemcachedCache;

class Cache extends MemcachedCache {
  private static $_servers = [];

  public static function initialize($host, $port, $weight) {
    self::$_servers[] = [
      'host'    => $host,
      'port'    => $port,
      'weight'  => $weight
    ];
  }

  private static $_instance = array();

  public static function getInstance($prefix) {

    if (empty(self::$_instance[$prefix])) {
      self::$_instance[$prefix] = new Cache($prefix, self::$_servers);
    }
    return self::$_instance[$prefix];
  }

}