<?php
require __DIR__ . "/User.php";     
require __DIR__ . "/../db/db.php";   


$user = new User([
    "id" => 0,
    "username" => "mohamad",
    "email" => "test@email.com",
    "password" => "" 
]);

$user->setPassword("secret123");


var_dump($user);

$id = User::create($connection, [
    "username" => $user->getUsername(),
    "email" => $user->getEmail(),
    "password" => $user->getPassword()
]);

echo "Inserted user with ID: $id";

// $user = User::find($connection, 1);
// var_dump($user);

// $user->setUsername("UpdatedName");
// User::update($connection, 1, ["username" => $user->getUsername()]);
// $user = User::find($connection, 1);

// if ($user->verifyPassword("secret1223")) {
//     echo "correct";
// } else {
//     echo "Wrong password";
// }
// User::delete($connection,1);