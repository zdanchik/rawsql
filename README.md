# rawsql
The simplest query builder

usage:

RawSql::select(Connection::getInstance('***'), '*')
      ->from('abs')
      ->andWhere('email = ?', "123@asdas.ss")
      ->andWhere("password = ?", "papapa")
      ->execute(true);