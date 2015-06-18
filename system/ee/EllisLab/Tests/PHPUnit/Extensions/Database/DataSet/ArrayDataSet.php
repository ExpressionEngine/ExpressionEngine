<?php

// HatTip: https://github.com/etsy/phpunit-extensions

//require_once 'PHPUnit/Extensions/Database/DataSet/AbstractDataSet.php';

namespace EllisLab\Tests\PHPUnit\Extensions\Database\DataSet;

class ArrayDataSet extends \PHPUnit_Extensions_Database_DataSet_AbstractDataSet {

    private $tables;

    public function __construct(
        array $data,
        array $tables=array()
    ) {
        $this->tables = $tables;
        foreach ($data as $table_name => $rows) {
            $table = new \PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
                    $table_name,
                    $this->getColumns($rows)
                )
            );
            foreach ($rows as $row) {
                $table->addRow($row);
            }
            $this->tables[$table_name] = $table;
        }
    }

    private function getColumns($rows) {
        $columns = array();
        foreach ($rows as $row) {
            $columns = array_merge($columns, array_keys($row));
        }
        return array_values(array_unique($columns));
    }

    protected function createIterator($reverse=false) {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultTableIterator(
            $this->tables,
            $reverse
        );
    }

    public function getTable($table_name) {
        if (!array_key_exists($table_name, $this->tables)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s is not a table in the current database.',
                    $table_name
                )
            );
        }
        return $this->tables[$table_name];
    }
}