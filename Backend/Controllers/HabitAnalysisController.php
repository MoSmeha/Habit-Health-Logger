<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../db/db.php';
require __DIR__ . '/../Services/HabitAnalysisService.php';
require __DIR__ . '/../Services/ResponseService.php';

class HabitAnalysisController
{
    public function handleRequest()
    {
        $action = $_GET['action'] ?? 'analyzeHabits';

        switch ($action) {
            case 'analyzeHabits':
                $this->analyzeHabits();
                break;
            case 'sleep_data':
                $this->getSleepData();
                break;
            case 'coffee_data':
                $this->getCoffeeData();
                break;
            default:
                echo ResponseService::response(400, "Invalid action");
                break;
        }
    }

    private function getUserId()
    {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId || !is_numeric($userId)) {
            echo ResponseService::response(400, "Missing or invalid user_id");
            exit;
        }
        return (int)$userId;
    }

    public function analyzeHabits()
    {
        global $connection;
        $userId = $this->getUserId();

        try {
            $result = HabitAnalysisService::analyzeHabits($connection, $userId);

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

    public function getSleepData()
    {
        global $connection;
        $userId = $this->getUserId();

        try {
            $data = HabitAnalysisService::getSleepData($connection, $userId);
            echo ResponseService::response(200, $data);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }

    public function getCoffeeData()
    {
        global $connection;
        $userId = $this->getUserId();

        try {
            $data = HabitAnalysisService::getCoffeeData($connection, $userId);
            echo ResponseService::response(200, $data);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }
}

$controller = new HabitAnalysisController();

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $controller->handleRequest();
} else {
    echo ResponseService::response(405, "Method Not Allowed");
}
