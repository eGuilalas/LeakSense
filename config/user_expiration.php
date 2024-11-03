<?php
// user_expiration.php

// Check if user has expired
function isUserExpired($userId, $db) {
    $query = "SELECT expiration_date FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $expirationDate = $result->fetch_assoc()['expiration_date'] ?? null;

    if ($expirationDate && strtotime($expirationDate) < time()) {
        return true;
    }
    return false;
}

// Check if user expiration date is within a given number of days
function isExpiringSoon($userId, $db, $days = 7) {
    $query = "SELECT expiration_date FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $expirationDate = $result->fetch_assoc()['expiration_date'] ?? null;

    if ($expirationDate) {
        $expirationTimestamp = strtotime($expirationDate);
        $threshold = strtotime("+$days days");
        return $expirationTimestamp < $threshold && $expirationTimestamp > time();
    }
    return false;
}
