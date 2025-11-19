<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../db/db.php';
require __DIR__ . '/../Services/HabitAnalysisService.php';
require __DIR__ . '/../Services/ResponseService.php';

class HabitAnalysisController
{
    public function analyzeHabits()
    {
        global $connection;

        $userId = $_GET['user_id'] ?? null;

        if (!$userId || !is_numeric($userId)) {
            echo ResponseService::response(400, "Missing or invalid user_id");
            return;
        }

        try {
            $result = HabitAnalysisService::analyzeHabits($connection, (int)$userId);

            if (!$result['ok']) {
                echo ResponseService::response(400, [
                    "error" => $result['error']
                ]);
                return;
            }

            echo ResponseService::response(200, [
                "habit_analysis" => $result['data']
            ]);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }
}

$controller = new HabitAnalysisController();

// Simple Router
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $controller->analyzeHabits();
} else {
    echo ResponseService::response(405, "Method Not Allowed");
}
