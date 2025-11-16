<?php

class ResponseService {

    public static function response(int $status_code, $payload){
        $response = [];
        $response["status"] = $status_code;
        $response["data"] = $payload;
        return json_encode($response);
    }
}

?>