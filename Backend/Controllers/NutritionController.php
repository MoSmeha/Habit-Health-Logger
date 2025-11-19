<?php
// MealSuggestionController.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../db/db.php';
require __DIR__ . '/../Services/MealSuggestionService.php';
require __DIR__ . '/../Services/ResponseService.php';

class MealSuggestionController
{

    // Get meal suggestions for a user based on their past week's diet

    public function getSuggestions()
    {
        global $connection;

        $userId = $_GET['user_id'] ?? null;

        if (!$userId || !is_numeric($userId)) {
            echo ResponseService::response(400, "Missing or invalid user_id");
            return;
        }

        try {
            $result = MealSuggestionService::getSuggestions($connection, (int)$userId);

            if (!$result['ok']) {
                echo ResponseService::response(400, [
                    "error" => $result['error']
                ]);
                return;
            }

            echo ResponseService::response(200, [
                "suggestions" => $result['data']
            ]);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }
}

$controller = new MealSuggestionController();

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        $controller->getSuggestions();
        break;
    default:
        echo ResponseService::response(405, "Method Not Allowed");
}
