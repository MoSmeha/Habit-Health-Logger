<?php
require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/Habit.php';
require __DIR__ . '/../Services/ResponseService.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class HabitController
{
    public function create()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($currentUserId === 0) {
            echo ResponseService::response(400, "Missing user_id");
            return;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            echo ResponseService::response(400, "Invalid JSON");
            return;
        }

        $name = $input['name'] ?? null;
        if (!$name) {
            echo ResponseService::response(400, "name is required");
            return;
        }

        try {
            $id = Habit::create($connection, [
                "name" => $name,
                "user_id" => $currentUserId,
                "active" => 1,
                "created_at" => date("Y-m-d H:i:s")
            ]);

            echo ResponseService::response(201, "Habit created", ["id" => $id]);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }

    public function update()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $habitId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($currentUserId === 0) {
            echo ResponseService::response(400, "Missing user_id");
            return;
        }
        if ($habitId === null) {
            echo ResponseService::response(400, "Missing habit id");
            return;
        }

        $habit = Habit::find($connection, $habitId);
        if (!$habit) {
            echo ResponseService::response(404, "Habit not found");
            return;
        }
        if ($habit->getUserId() !== $currentUserId) {
            echo ResponseService::response(403, "Not allowed");
            return;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        $data = [];
        if (isset($input['name'])) $data['name'] = $input['name'];
        if (isset($input['active'])) $data['active'] = $input['active'] ? 1 : 0;

        if (empty($data)) {
            echo ResponseService::response(400, "Nothing to update");
            return;
        }

        Habit::update($connection, $habitId, $data);
        echo ResponseService::response(200, "Habit updated");
    }


    public function delete()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $habitId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($currentUserId === 0) {
            echo ResponseService::response(400, "Missing user_id");
            return;
        }
        if ($habitId === null) {
            echo ResponseService::response(400, "Missing habit id");
            return;
        }

        $habit = Habit::find($connection, $habitId);
        if (!$habit) {
            echo ResponseService::response(404, "Habit not found");
            return;
        }
        if ($habit->getUserId() !== $currentUserId) {
            echo ResponseService::response(403, "Not allowed");
            return;
        }

        Habit::delete($connection, $habitId);
        echo ResponseService::response(200, "Habit deleted");
    }

    public function list()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($currentUserId === 0) {
            echo ResponseService::response(400, "Missing user_id");
            return;
        }

        $habits = Habit::findBy($connection, "user_id", $currentUserId);
        $data = [];
        foreach ($habits as $h) {
            $data[] = $h->toArray();
        }

        echo ResponseService::response(200, $data);
    }
}

// Minimal routing
$controller = new HabitController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $controller->list();
        break;
    case 'POST':
        $controller->create();
        break;
    case 'PATCH':
        $controller->update();
        break;
    case 'DELETE':
        $controller->delete();
        break;
    default:
        echo ResponseService::response(405, "Method Not Allowed");
}
