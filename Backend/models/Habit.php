<?php

require_once __DIR__ . '/Model.php';

class Habit extends Model {
    private int $id;
    private string $name;
    private int $user_id;
    private int $active;
    private string $created_at;

    protected static string $table = "habits";

    public function __construct(array $data) {
        $this->id = $data["id"];
        $this->name = $data["name"];
        $this->user_id = $data["user_id"];
        $this->active = $data["active"];
        $this->created_at = $data["created_at"] ?? date("Y-m-d H:i:s");
    }

    public function getId() {
            return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getUserId() {
        return $this->user_id;
    }
    public function isActive() {
        return $this->active == 1;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function setActive(bool $active) {
        $this->active = $active ? 1 : 0;
    }

    public function toArray() {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "user_id" => $this->user_id,
            "active" => $this->active,
            "created_at" => $this->created_at
        ];
    }

    public function __toString() {
        return $this->id . " | " . $this->name . " | user:" . $this->user_id . " | active:" . $this->active;
    }

}