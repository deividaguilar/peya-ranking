<?php

namespace AppBundle\Service;

class ValidatorService
{
    private static $endPoints = [
        "get_score" => [],
        "save_score" => [        
            "user_id" => "int",
            "store_id" => "int",
            "buy_id" => "int",
            "score" => "int",
            "date" => "datetime",
            "opinions" => "string"
        ],
        "get_score_by_user" => [
            "id" => "int",
            "date1" => "datetime",
            "date2" => "datetime",
        ],
        "get_score_by_id" => [
            "id" => "string"
        ],
        "get_score_by_store" => [
            "id" => "int",
            "date1" => "datetime",
            "date2" => "datetime",
        ],
        "update_score" => [
            'id' => "string",
            'score' => "int",
            'date' => "datetime",
            'opinions' => "string"
        ],
        "delete_score" => [
            'id' => "string"
        ],
        "delete_all_score" => []
    ];

    public function executeValidation($parameters, $endPoint)
    {   
        $parameters = $parameters === null ? [] : $parameters; 
        $difference = array_diff_key($parameters, self::$endPoints[$endPoint]);
        if (!empty($difference) || count($parameters) != count(self::$endPoints[$endPoint])){
            return false;
        }
        return true;
    }
}