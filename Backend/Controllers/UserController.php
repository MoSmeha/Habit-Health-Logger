<?php
require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../Services/ResponseService.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class UserController
{
    private const ADMIN_ID = 21;

    public function create()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($currentUserId !== self::ADMIN_ID) {
            echo ResponseService::response(403, "Admin access required");
            return;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            echo ResponseService::response(400, "Invalid JSON");
            return;
        }

        $username = $input['username'] ?? null;
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$username || !$email || !$password) {
            echo ResponseService::response(400, "username, email, and password are required");
            return;
        }

        try {
            $id = User::create($connection, [
                "username" => $username,
                "email" => $email,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "role" => $input['role'] ?? "user",
                "created_at" => date("Y-m-d H:i:s")
            ]);

            echo ResponseService::response(201, "User created", ["id" => $id]);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }

    public function update()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($currentUserId !== self::ADMIN_ID) {
            echo ResponseService::response(403, "Admin access required");
            return;
        }
        if ($userId === null) {
            echo ResponseService::response(400, "Missing user id");
            return;
        }

        $user = User::find($connection, $userId);
        if (!$user) {
            echo ResponseService::response(404, "User not found");
            return;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        $data = [];
        if (isset($input['username'])) $data['username'] = $input['username'];
        if (isset($input['email'])) {
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                echo ResponseService::response(400, "Invalid email format");
                return;
            }
            $data['email'] = $input['email'];
        }
        if (isset($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (isset($input['role'])) $data['role'] = $input['role'];

        if (empty($data)) {
            echo ResponseService::response(400, "Nothing to update");
            return;
        }

        User::update($connection, $userId, $data);
        echo ResponseService::response(200, "User updated");
    }

    public function delete()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($currentUserId !== self::ADMIN_ID) {
            echo ResponseService::response(403, "Admin access required");
            return;
        }
        if ($userId === null) {
            echo ResponseService::response(400, "Missing user id");
            return;
        }

        $user = User::find($connection, $userId);
        if (!$user) {
            echo ResponseService::response(404, "User not found");
            return;
        }

        User::delete($connection, $userId);
        echo ResponseService::response(200, "User deleted");
    }

    public function list()
    {
        global $connection;

        $currentUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($currentUserId !== self::ADMIN_ID) {
            echo ResponseService::response(403, "Admin access required");
            return;
        }

        $users = User::findAll($connection);
        $data = [];
        foreach ($users as $u) {
            $data[] = $u->toArray();
        }

        echo ResponseService::response(200, $data);
    }
}

// Minimal routing
$controller = new UserController();

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
