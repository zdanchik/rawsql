<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 23.12.2014
 * Time: 18:01
 */

namespace RawSql;

use PDO;
use PDOException;

class Connection {

  static $PDO_OPTIONS = array(
    PDO::ATTR_CASE => PDO::CASE_LOWER,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
    PDO::ATTR_STRINGIFY_FETCHES => false);

  public static function parse_connection_url($connection_url)
  {
    $url = @parse_url($connection_url);

    if (!isset($url['host']))
      throw new \Exception('Database host must be specified in the connection string. If you want to specify an absolute filename, use e.g. sqlite://unix(/path/to/file)');

    $info = new \stdClass();
    $info->protocol = $url['scheme'];
    $info->host = $url['host'];
    $info->db = isset($url['path']) ? substr($url['path'], 1) : null;
    $info->user = isset($url['user']) ? $url['user'] : null;
    $info->pass = isset($url['pass']) ? $url['pass'] : null;

    $allow_blank_db = ($info->protocol == 'sqlite');

    if ($info->host == 'unix(')
    {
      $socket_database = $info->host . '/' . $info->db;

      if ($allow_blank_db)
        $unix_regex = '/^unix\((.+)\)\/?().*$/';
      else
        $unix_regex = '/^unix\((.+)\)\/(.+)$/';

      if (preg_match_all($unix_regex, $socket_database, $matches) > 0)
      {
        $info->host = $matches[1][0];
        $info->db = $matches[2][0];
      }
    } elseif (substr($info->host, 0, 8) == 'windows(')
    {
      $info->host = urldecode(substr($info->host, 8) . '/' . substr($info->db, 0, -1));
      $info->db = null;
    }

    if ($allow_blank_db && $info->db)
      $info->host .= '/' . $info->db;

    if (isset($url['port']))
      $info->port = $url['port'];

    if (strpos($connection_url, 'decode=true') !== false)
    {
      if ($info->user)
        $info->user = urldecode($info->user);

      if ($info->pass)
        $info->pass = urldecode($info->pass);
    }

    if (isset($url['query']))
    {
      foreach (preg_split('/&/', $url['query']) as $pair) {
        list($name, $value) = preg_split('/=/', $pair);

        if ($name == 'charset')
          $info->charset = $value;
      }
    }

    return $info;
  }

  private static $connections = array();

  /**
   * @param $connection_string
   *
   * @return Connection
   * @throws \Exception
   */
  public static function getInstance($connection_string) {
    if (!$connection_string)
      throw new \Exception("Empty connection string");

    if (empty(self::$cfg[$connection_string]))
      throw new \Exception("Empty config $connection_string");

    $info = static::parse_connection_url(self::$cfg[$connection_string]);
    if (empty(self::$connections[$connection_string])) {
      try {
        $connection = new self($info);
        if (isset($info->charset))
          $connection->set_encoding($info->charset);
      } catch (PDOException $e) {
        throw new \Exception($e);
      }
      self::$connections[$connection_string] = $connection;
    }
    return self::$connections[$connection_string];
  }

  private static $cfg = array();
  public static function initialize($connections) {
    self::$cfg = $connections;
  }

  private $_conn_string = null;
  protected function __construct($info)
  {
    try {
      // unix sockets start with a /
      if ($info->host[0] != '/')
      {
        $host = "host=$info->host";

        if (isset($info->port))
          $host .= ";port=$info->port";
      }
      else
        $host = "unix_socket=$info->host";
      $this->_conn_string = "$info->protocol:$host;dbname=$info->db;charset=$info->charset";
      $this->connection = new PDO($this->_conn_string, $info->user, $info->pass, static::$PDO_OPTIONS);
    } catch (PDOException $e) {
      throw new \Exception($e);
    }
  }

  public function set_encoding($charset)
  {
    $params = array($charset);
    $stmt = $this->prepare('SET NAMES ?');
    $stmt->execute($params);
  }


  public function prepare($stmt, $params = array()) {
    return $this->connection->prepare($stmt, $params);
  }


  public function errorCode() {
    return $this->connection->errorCode();
  }

  public function lastInsertId($name = null) {
    return $this->connection->lastInsertId($name);
  }

  public function beginTransaction() {
    $this->connection->beginTransaction();
  }

  public function commit() {
    $this->connection->commit();
  }

  public function rollback() {
    $this->connection->rollBack();
  }

  public function quote($value) {
    return $this->connection->quote($value);
  }

  public function __toString() {
    return $this->_conn_string;
  }

  public function inTransaction()
  {
    return $this->connection->inTransaction();
  }

} 