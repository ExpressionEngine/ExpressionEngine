<?php

class Structure_Nestedset_Adapter_Ee
{
    private $dbh;
    private $table;
    private $leftCol;
    private $rightCol;
    private $idCol;
    private $site_id;

    public function __construct($table, $leftCol = 'lft', $rightCol = 'rgt', $id = 'id')
    {
        $this->table    = $table;
        $this->leftCol  = $leftCol;
        $this->rightCol = $rightCol;
        $this->idCol    = $id;
        $this->site_id  = ee()->config->item('site_id');
    }

    public function getTree($node = 0, $includeNode = true, $directOnly = false)
    {
        $table   = $this->table;
        $leftCol = $this->leftCol;
        $rightCol = $this->rightCol;

        if ($node) {
            if (! is_array($node)) {
                $node = $this->getNodeById($node);
            }

            extract($node);

            if ($includeNode) {
                $sql = "SELECT *
                        FROM $table
                        WHERE $leftCol >= $left AND $rightCol <= $right
                        AND site_id = $this->site_id
                        ORDER BY $leftCol";
            } else {
                $sql = "SELECT *
                        FROM $table
                        WHERE $leftCol > $left AND $rightCol < $right
                        AND site_id = $this->site_id
                        ORDER BY $leftCol";
            }
        } else {
            $sql = "SELECT *
                    FROM $table
                    WHERE site_id = $this->site_id
                    ORDER BY $leftCol";
        }
        return ee()->db->query($sql)->result_array();
    }

    public function getNodeById($id)
    {
        $table = $this->table;
        $idCol = $this->idCol;

        /*
        $sql = "SELECT *
                FROM $table
                WHERE $idCol = '$id'
                LIMIT 1";
        */

        $sql = "SELECT node.*,
                    (COUNT(parent.lft) - 2) AS depth,
                    if((node.rgt - node.lft) = 1,1,0) AS isLeaf,
                    ((node.rgt - node.lft - 1) DIV 2) AS numChildren
                FROM $table AS node
                INNER JOIN $table AS parent
                    ON node.lft BETWEEN parent.lft AND parent.rgt
                WHERE node.$idCol = '$id'
                AND parent.site_id IN (0, $this->site_id)
                GROUP BY node.lft
                LIMIT 1";

        $result = ee()->db->query($sql);

        if ($result->num_rows == 0) {
            return false;
        }

        $result_row = "";
        foreach ($result->result_array() as $row) {
            $result_row = $row;
        }

        return $this->formatNode($result_row);
    }

    public function getNodeBySet($left = 0, $right = 0)
    {
        $table = $this->table;
        $where = '';

        if ($left) {
            $where = 'node.' . $this->leftCol . ' = ' . $left . ' AND ';
        }

        if ($right) {
            $where .= 'node.' . $this->rightCol . ' = ' . $right . ' AND ';
        }

        /*
        $sql = "SELECT *
                FROM $table
                WHERE $where
                LIMIT 1";
        */

        // MySQL 5.7.5 workaround. TODO: Come back to this and rewrite these queries.
        ee()->db->query("SET SESSION sql_mode = ''");


        $sql = "SELECT node.*,
                    (COUNT(parent.lft) - 2) AS depth,
                    if((node.rgt - node.lft) = 1,1,0) AS isLeaf,
                    ((node.rgt - node.lft - 1) DIV 2) AS numChildren
                FROM $table AS node
                INNER JOIN $table AS parent
                    ON node.lft BETWEEN parent.lft AND parent.rgt
                WHERE $where
                node.site_id = $this->site_id
                GROUP BY node.lft
                LIMIT 1";

        $result = ee()->db->query($sql);

        if ($result->num_rows == 0) {
            return false;
        }

        $result_row = "";
        foreach ($result->result_array() as $row) {
            $result_row = $row;
        }

        return $this->formatNode($result_row);
    }

    /**
     * Returns node depth. (root depth = 0)
     * @param
     * @return
     */
    public function getDepth($node)
    {
        $table = $this->table;

        if (! is_array($data)) {
            $node = $this->getNodeById($node);
        }

        $where  = $this->leftCol . ' < ' . $node['left'];
        $where .= ' AND ' . $this->rightCol . ' > ' . $node['right'];

        $sql = "SELECT COUNT(*) AS depth
                FROM $table
                WHERE $where
                AND site_id = $this->site_id";

        $result = ee()->db->query($sql);

        if (isset($result->row['depth'])) {
            return $result->row['depth'];
        } else {
            return false;
        }
    }

    public function insertNode($left, $right, array $extra = array())
    {
        $table   = $this->table;
        $leftCol    = $this->leftCol;
        $rightCol   = $this->rightCol;
        $columns = '';
        $values  = '';

        if ($extra !== array()) {
            foreach ($extra as $column => $value) {
                $columns .= $column . ', ';
                $value = ee()->db->escape_str($value);
                $value = (! is_numeric($value) && (is_string($value) || $value === '')) ? "'" . $value . "'" : $value;
                $values  .= $value . ',';
            }
        }

        $sql = "INSERT INTO $table ($columns $leftCol, $rightCol)
                VALUES ($values $left, $right)";

        ee()->db->query($sql);
    }


    public function deleteNodeTree($left, $right)
    {
        $table   = $this->table;
        $leftCol    = $this->leftCol;
        $rightCol   = $this->rightCol;

        $sql = "DELETE FROM $table
                WHERE $leftCol >= $left AND $rightCol <= $right
                AND site_id = $this->site_id";
        ee()->db->query($sql);
        $this->shift($right + 1, $left - $right - 1);
    }


    public function shift($first, $delta)
    {
        $table    = $this->table;
        $leftCol  = $this->leftCol;
        $rightCol = $this->rightCol;

        $sql   = array();
        $sql[] = "UPDATE $table
                    SET $leftCol = $leftCol + $delta
                    WHERE $leftCol >= $first
                    AND site_id = $this->site_id";
        $sql[] = "UPDATE $table
                    SET $rightCol = $rightCol + $delta
                    WHERE $rightCol >= $first
                    AND site_id = $this->site_id";

        foreach ($sql as $stmt) {
            ee()->db->query($stmt);
        }
    }

    public function shiftRange($first, $last, $delta)
    {
        $table = $this->table;
        $leftCol  = $this->leftCol;
        $rightCol = $this->rightCol;

        $sql = array();
        $sql[] = "UPDATE $table
                    SET $leftCol = $leftCol + $delta
                    WHERE $leftCol >= $first AND $leftCol <= $last
                    AND site_id = $this->site_id";
        $sql[] = "UPDATE $table
                    SET $rightCol = $rightCol + $delta
                    WHERE $rightCol >= $first  AND $rightCol <= $last
                    AND site_id = $this->site_id";

        foreach ($sql as $stmt) {
            ee()->db->query($stmt);
        }

        return array(
            'left'  => $first + $delta,
            'right' => $last + $delta
        );
    }

    public function begin()
    {
        $sql = 'LOCK TABLE ' . $this->table . ' WRITE';
        ee()->db->query($sql);
    }

    public function end()
    {
        $sql = 'UNLOCK TABLES';
        ee()->db->query($sql);
    }

    private function formatNode($data)
    {
        $node = array(
            'id'    => $data[$this->idCol],
            'left'  => $data[$this->leftCol],
            'right' => $data[$this->rightCol]
        );
        unset($data[$this->idCol], $data[$this->leftCol], $data[$this->rightCol]);

        return array_merge($node, $data);
    }
}
/* END Class */

/* End of file structure_nestedset_adapter_ee.php */
/* Location: ./system/expressionengine/modules/structure/libraries/nestedset/structure_nestedset_adapter_ee.php */
