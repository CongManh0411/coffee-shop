<?php
class Database
{
    private $connection;

    public function __construct($config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->connection = new mysqli(
            $config['host'], $config['username'], $config['password'],
            $config['database'], $config['port']
        );
        $this->connection->set_charset($config['charset']);
    }

    public function run($sql, $params = array())
    {
        $stmt = $this->connection->prepare($sql);
        if ($params) {
            $types = '';
            foreach ($params as $p) { $types .= is_int($p) ? 'i' : 's'; }
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    public function fetchAll($sql, $params = array())
    {
        return $this->run($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchOne($sql, $params = array())
    {
        $row = $this->run($sql, $params)->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function execute($sql, $params = array())
    {
        return $this->run($sql, $params)->affected_rows;
    }

    public function insertId()  { return $this->connection->insert_id; }
    public function begin()     { $this->connection->begin_transaction(); }
    public function commit()    { $this->connection->commit(); }
    public function rollback()  { $this->connection->rollback(); }
}