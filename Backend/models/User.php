<?php
include("Model.php");

class User extends Model {
    private int $id;
    private string $username;
    private string $email;
    private string $password;
    private string $created_at;

    protected static string $table = "users";

    public function __construct(array $data){
        $this->id         = $data["id"];
        $this->username   = $data["username"];
        $this->setEmail($data["email"]);
        $this->password   = $data["password"];     // hashed password from DB
        $this->created_at = $data["created_at"] ?? date("Y-m-d H:i:s");
    }


    public function getID(){
        return $this->id;
    }

    public function setUsername(string $username){
        $this->username = $username;
    }

    public function getUsername(){
        return $this->username;
    }

    public function setEmail(string $email){
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email format: $email");
    }
    $this->email = $email;
}

    public function getEmail(){
        return $this->email;
    }

    public function setPassword(string $password){
        // Apparently the built in hash(sha256..) is not secure and not recommended
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function getPassword(){
        return $this->password;
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function getCreatedAt(){
        return $this->created_at;
    }

    // excluding password
    public function __toString(){
        return $this->id . " | " . $this->username . " | " . $this->email . " | " . $this->created_at;
    }

    public function toArray(){
        return [
            "id"         => $this->id,
            "username"   => $this->username,
            "email"      => $this->email,
            "created_at" => $this->created_at
        ];
    }
}

?>
