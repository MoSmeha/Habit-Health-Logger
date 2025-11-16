<?php

require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../Services/ResponseService.php';

class SignupController {

    public function register() {
        global $connection;
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $email    = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        if (!$username || !$email || !$password) {
            echo ResponseService::response(400, "username, email and password are required");
            return;
        }
        try {
            $tmpUser = new User([
                'id' => 0,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'created_at' => date("Y-m-d H:i:s"),
                'role'=> 'user'
            ]);
        } catch (Exception $e) {
            echo ResponseService::response(400, $e->getMessage());
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $id = User::create($connection, [
                "username" => $username,
                "email"    => $email,
                "password" => $hash
            ]);
        } catch (mysqli_sql_exception $e) {
            // unique email 
        if ($e->getCode() === 1062) {  
            echo ResponseService::response(400, "Email already registered");
        } else {
            echo ResponseService::response(500, "Could not create user".$e->getMessage());
        }
        return;
        }

        $userObj = User::find($connection, $id);
        echo ResponseService::response(200, $userObj->toArray());
    }
}


$signup = new SignupController();
$signup->register();
