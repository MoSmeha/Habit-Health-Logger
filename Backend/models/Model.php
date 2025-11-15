<?php
abstract class Model {

    protected static string $table;
    protected static string $primary_key = "id";

    public static function find(mysqli $connection, $id) {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = ? LIMIT 1",
            static::$table,
            static::$primary_key
        );

        $stmt = $connection->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $data = $stmt->get_result()->fetch_assoc();
        return $data ? new static($data) : null;
    }

    public static function findAll(mysqli $connection): array {
        $sql = sprintf(
            "SELECT * FROM %s",
            static::$table
        );

        $stmt = $connection->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = new static($row);
        }

        return $rows;
    }

    public static function create(mysqli $connection, array $data): int {
        $columns = array_keys($data);     
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $cols = implode(',', $columns);  

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            $cols,
            $placeholders
        );

        $stmt = $connection->prepare($sql);

        $values = array_values($data);
        $types = "";
        foreach ($values as $v) {
            if (is_int($v)) {
                $types .= "i";
            } else if (is_float($v)) {
                $types .= "d";
            } else {
                $types .= "s";
            }
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $connection->insert_id;
    }

    public static function update(mysqli $connection, $id, array $data): bool {
        $columns = array_keys($data);

        // build "col1 = ?, col2 = ?, ..."
        $setParts = [];
        foreach ($columns as $col) {
            $setParts[] = "$col = ?";
        }
        $setClause = implode(', ', $setParts);

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            static::$table,
            $setClause,
            static::$primary_key
        );

        $stmt = $connection->prepare($sql);

        $values = array_values($data);
        $types = "";
        foreach ($values as $v) {
            if (is_int($v)) {
                $types .= "i";
            } else if (is_float($v)) {
                $types .= "d";
            } else {
                $types .= "s";
            }
        }

        $types  = str_repeat('s', count($data)) . "i";

        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public static function delete(mysqli $connection, $id): bool {
        $sql = sprintf(
            "DELETE FROM %s WHERE %s = ?",
            static::$table,
            static::$primary_key
        );

        $stmt = $connection->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}
?>
