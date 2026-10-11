<?php
/**
 * Indian Cities Search API
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');
if (strlen($query) === 0) {
    // Return top popular cities by default
    $db = getDB();
    $stmt = $db->query("SELECT id, name, state, is_popular FROM indian_cities WHERE is_popular = 1 ORDER BY name ASC LIMIT 15");
    $cities = $stmt->fetchAll();
    echo json_encode(['success' => true, 'cities' => $cities]);
    exit;
}

$db = getDB();
$searchTerm = '%' . $query . '%';
$prefixTerm = $query . '%';

$stmt = $db->prepare("SELECT id, name, state, is_popular,
                      CASE 
                        WHEN name LIKE ? THEN 1
                        WHEN name LIKE ? THEN 2
                        WHEN state LIKE ? THEN 3
                        ELSE 4 
                      END AS match_priority
                      FROM indian_cities 
                      WHERE name LIKE ? OR state LIKE ?
                      ORDER BY match_priority ASC, is_popular DESC, name ASC 
                      LIMIT 20");

$stmt->execute([$prefixTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
$cities = $stmt->fetchAll();

echo json_encode(['success' => true, 'cities' => $cities]);
