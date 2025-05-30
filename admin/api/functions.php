<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/backend/db/functions.php

require_once 'db_connection.php';

/**
 * Fetch all rows from a table.
 *
 * @param string $tableName The name of the table.
 * @return array The fetched rows.
 */
function fetchAll($tableName) {
    global $conn;
    try {
        $stmt = $conn->query("SELECT * FROM $tableName");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching all rows from $tableName: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch a single row by ID.
 *
 * @param string $tableName The name of the table.
 * @param string $idColumn The name of the ID column.
 * @param int $id The ID value.
 * @return array|null The fetched row or null if not found.
 */
function fetchById($tableName, $idColumn, $id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM $tableName WHERE $idColumn = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching row by ID from $tableName: " . $e->getMessage());
        return null;
    }
}

/**
 * Insert a new row into a table.
 *
 * @param string $tableName The name of the table.
 * @param array $data An associative array of column-value pairs.
 * @return bool True on success, false on failure.
 */
function insertRow($tableName, $data) {
    global $conn;
    try {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute($data);
    } catch (PDOException $e) {
        error_log("Error inserting row into $tableName: " . $e->getMessage());
        return false;
    }
}

/**
 * Update a row in a table.
 *
 * @param string $tableName The name of the table.
 * @param string $idColumn The name of the ID column.
 * @param int $id The ID value.
 * @param array $data An associative array of column-value pairs.
 * @return bool True on success, false on failure.
 */
function updateRow($tableName, $idColumn, $id, $data) {
    global $conn;
    try {
        $setClause = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($data)));
        $sql = "UPDATE $tableName SET $setClause WHERE $idColumn = :id";
        $stmt = $conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    } catch (PDOException $e) {
        error_log("Error updating row in $tableName: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a row from a table.
 *
 * @param string $tableName The name of the table.
 * @param string $idColumn The name of the ID column.
 * @param int $id The ID value.
 * @return bool True on success, false on failure.
 */
function deleteRow($tableName, $idColumn, $id) {
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM $tableName WHERE $idColumn = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error deleting row from $tableName: " . $e->getMessage());
        return false;
    }
}

/**
 * Search rows in a table by a keyword.
 *
 * @param string $tableName The name of the table.
 * @param array $columns The columns to search in.
 * @param string $keyword The search keyword.
 * @return array The search results.
 */
function searchRows($tableName, $columns, $keyword) {
    global $conn;
    try {
        $likeClause = implode(' OR ', array_map(fn($col) => "$col LIKE :keyword", $columns));
        $sql = "SELECT * FROM $tableName WHERE $likeClause";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':keyword', "%$keyword%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error searching rows in $tableName: " . $e->getMessage());
        return [];
    }
}

/**
 * Count the total number of rows in a table.
 *
 * @param string $tableName The name of the table.
 * @return int The total row count.
 */
function countRows($tableName) {
    global $conn;
    try {
        $stmt = $conn->query("SELECT COUNT(*) AS total FROM $tableName");
        $result = $stmt->fetch();
        return $result['total'];
    } catch (PDOException $e) {
        error_log("Error counting rows in $tableName: " . $e->getMessage());
        return 0;
    }
}