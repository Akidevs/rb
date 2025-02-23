<?php
// includes/utils.php

/**
 * Safely fetches data from the database.
 *
 * @param PDO $conn The PDO connection object.
 * @param string $query The SQL query with placeholders.
 * @param array $params The parameters to bind in the query.
 * @return array|false The fetched data or false on failure.
 */
function fetchData(PDO $conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error details
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches all rows from a query.
 *
 * @param PDO $conn The PDO connection object.
 * @param string $query The SQL query with placeholders.
 * @param array $params The parameters to bind in the query.
 * @return array The fetched data.
 */
function fetchAllData(PDO $conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error details
        error_log("Database Error: " . $e->getMessage());
        return [];
    }
}
?>