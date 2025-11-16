<?php

require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../Services/ResponseService.php';

class LoginController {
  
    public function login() {
        global $connection;
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            echo ResponseService::response(400, "email and password are required");
            return;
        }

        $user = User::findBy($connection, "email", $email);
        if (!$user || !$user->verifyPassword($password)) {
            echo ResponseService::response(401, "Invalid credentials");
            return;
        }

        echo ResponseService::response(200, $user->toArray());
    }
}

$login = new LoginController();
$login->login();