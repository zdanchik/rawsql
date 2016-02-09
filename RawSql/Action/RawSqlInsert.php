<?php
namespace RawSql\Action;
use RawSql\RawArray;
use RawSql\RawSql;

/**
 * Created by JetBrains PhpStorm.
 * User: slomakov
 * Date: 03.09.13
 * Time: 16:39
 * To change this template use File | Settings | File Templates.
 */
class RawSqlInsert extends RawSql
{
  protected $insert = null;
  protected $columns = null;
  protected $on_duplicate = null;
  protected $last_insert_id = null;
  protected $ignore = false;

  /**
   * @param       $connection
   * @param null  $table_name
   * @param array $columns
   *
   * @return RawSqlInsert
   */
  function __construct($connection, $table_name = null, $columns)
  {
    $this->_conn = $connection;
    if ($table_name) {
      $this->table_name = $table_name;
    }
    if (!is_array($columns) || empty($columns)) {
      throw new \Exception("Columns parameter must be array with at least one argument");
    }
    $this->columns = $columns;

    return $this;
  }

  // TODO: add support for prepared statement
  public function onDuplicateKey($string)
  {
    $this->on_duplicate = $string;

    return $this;
  }

// TODO: add support for prepared statement
  private function getOnDuplicateKeySQL()
  {
    return $this->on_duplicate;
  }

  public function insertIgnore()
  {
    $this->ignore = true;
    return $this;
  }

  private function getInsertIgnore()
  {
    return $this->ignore;
  }

  /**
   * @param $table_name
   *
   * @return RawSqlInsert
   */
  public function setValues()
  {
    $params = func_get_args();
    $this->processInsertArguments($params);

    return $this;
  }

  public function setValuesFromArray($params)
  {
    $result = array();
    foreach ($this->columns as $column) {
      if (!array_key_exists($column, $params)) {
        throw new \Exception('Not set value for column ' . $column);
      }

      $result[] = $params[$column];
    }

    $this->processInsertArguments($result);

    return $this;
  }

  protected function getInsertSql()
  {
    return join(', ', $this->insert);
  }

  private function processInsertArguments($params)
  {
    $inserts = array();
    // first parameter is query part
    foreach ($this->columns as $col) {
      $inserts[] = '?';
    }
    $this->insert[] = '(' . join(', ', $inserts) . ')';

    foreach ($params as $param) {
      $this->args[] = $param;
    }
  }

  protected function getColumnsSql()
  {
    return '(`' . join('`, `', $this->columns) . '`)';
  }

  /**
   * @return string
   */
  public function getSql()
  {
    $insert_ignore = ($this->getInsertIgnore()) ? "IGNORE" : "";

    $sql_arr = array(
      "INSERT {$insert_ignore} INTO {$this->table_name}",
      $this->getColumnsSql(), // columns
      "VALUES {$this->getInsertSql()}"
    );
    if (!empty($this->on_duplicate)) {
      $sql_arr[] = 'ON DUPLICATE KEY ' . $this->getOnDuplicateKeySQL();
    }

    return implode(" ", $sql_arr);
  }

  /**
   * @param $connection
   *
   * @return RawArray
   */
  public function execute()
  {
    if ($this->_conn) {
      $statement = $this->getConnection()->prepare($this);
      $statement->execute($this->getArgs());
      $this->last_insert_id = $this->getConnection()->lastInsertId();
      $this->insert = null;
      $this->args   = null;

      return is_numeric($this->last_insert_id);
    }
    return null;
  }

  public function getLastInsertId()
  {
    return $this->last_insert_id;
  }
}
