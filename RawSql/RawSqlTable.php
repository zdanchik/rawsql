<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 12.01.2015
 * Time: 20:19
 */

namespace RawSql;


class RawSqlTable {

  protected static function getAllowedFields($table) {
    return RawSql::select(Connection::getInstance('loancrm'), 'COLUMN_NAME')
      ->from('INFORMATION_SCHEMA.COLUMNS')
      ->andWhere("table_name = ?", $table)
      ->execute()
      ->parse('column_name');
  }

} 