<?php

require_once __DIR__ . '/Model.php';

class UserEntry extends Model
{
    private int $id;
    private int $user_id;
    private string $input_text;
    private string $created_at;

    protected static string $table = "user_entries";

    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->user_id = $data["user_id"];
        $this->input_text = $data["input_text"];
        $this->created_at = $data["created_at"] ?? date("Y-m-d H:i:s");
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getInputText(): string
    {
        return $this->input_text;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function setInputText(string $text)
    {
        $this->input_text = $text;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "input_text" => $this->input_text,
            "created_at" => $this->created_at
        ];
    }

    public function __toString(): string
    {
        return $this->id . " | user:" . $this->user_id . " | text:" . $this->input_text;
    }
}
