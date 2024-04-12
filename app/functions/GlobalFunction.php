<?php

namespace App\Functions;

use App\Response\Message;

class GlobalFunction
{
    // SUCCESS
    public static function save($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::CREATED_STATUS
        );
    }
    public static function responseFunction($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::SUCESS_STATUS
        );
    }
    public static function notFound($message)
    {
        return response()->json(
            [
                "message" => $message,
            ],
            Message::DATA_NOT_FOUND
        );
    }

    public static function invalid($message)
    {
        return response()->json(
            [
                "message" => $message,
            ],
            Message::UNPROCESS_STATUS
        );
    }

    public static function denied($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::DENIED_STATUS
        );
    }
}
