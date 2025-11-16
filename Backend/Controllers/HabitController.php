<?php
require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/Habit.php';
require __DIR__ . '/../Services/ResponseService.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class HabitController
{
    public function create($currentUserId)
    {
        global $connection;

        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!is_array($input)) {
                echo ResponseService::response(400, "Invalid JSON");
                return;
            }

            $name = $input["name"] ?? null;
            if (!$name) {
                echo ResponseService::response(400, "name is required");
                return;
            }

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

    // update habit name or active
    public function update($currentUserId, $habitId)
    {
        global $connection;
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
        if (isset($input["name"])) $data["name"] = $input["name"];
        if (isset($input["active"])) $data["active"] = $input["active"] ? 1 : 0;


        if (empty($data)) {
            echo ResponseService::response(400, "Nothing to update");
            return;
        }


        Habit::update($connection, $habitId, $data);
        echo ResponseService::response(200, "Habit updated");
    }


    // delete
    public function delete($currentUserId, $habitId)
    {
        global $connection;
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

    public function list($userId)
    {
        global $connection;
        $habits = Habit::findBy($connection,  "user_id", $userId);


        $data = [];
        foreach ($habits as $h) {
            $data[] = $h->toArray();
        }


        echo ResponseService::response(200, "OK", $data);
    }
}
$con = new HabitController();
$con->create(22);
