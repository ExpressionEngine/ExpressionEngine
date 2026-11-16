<?php

namespace {
    if (!function_exists('bool_config_item')) {
        function bool_config_item($key)
        {
            return ee()->config->item($key) === 'y';
        }
    }
}

namespace ExpressionEngine\Controller\Design {
    if (!function_exists(__NAMESPACE__ . '\\show_error')) {
        function show_error($message, $status = 500)
        {
            throw new \RuntimeException($message, $status);
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Design {
    /**
     * Only the SQL transport is replaced. The production query builder, model
     * queries, relationships, validation and lifecycle events all run normally.
     */
    class TemplateFixtureQuery extends \ExpressionEngine\Service\Database\Query
    {
        private $pdo;

        public function __construct(\PDO $pdo)
        {
            $this->pdo = $pdo;
            $this->dbprefix = 'exp_';
        }

        public function query($sql, $binds = false, $return_object = true)
        {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($binds === false ? [] : $binds);

            return new \eeDbResultMock($statement->columnCount()
                ? $statement->fetchAll(\PDO::FETCH_ASSOC) : []);
        }

        public function escape_str($str, $like = false)
        {
            return str_replace("'", "''", (string) $str);
        }

        public function insert_id()
        {
            return (int) $this->pdo->lastInsertId();
        }

        public function _limit($sql, $limit, $offset)
        {
            // Translate the model builder's MySQL "unlimited" sentinel.
            return parent::_limit($sql, $limit === '18446744073709551615' ? -1 : $limit, $offset);
        }
    }

    class TemplateFixtureRedirect extends \RuntimeException
    {
    }

    class TemplateFixtureValidationFailure extends \RuntimeException
    {
    }
}
