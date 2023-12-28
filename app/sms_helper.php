<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

if (!function_exists('SMSAKGOVT')) {
    function SMSAKGOVT($mobileno, $message, $templateid = null)
    {
        if (strlen($mobileno) == 10 && is_numeric($mobileno) && $templateid != NULL) {
            $username = Config::get("constants.akola_user_name"); #username of the department
            $password = Config::get("constants.akola_password"); #password of the department
            $senderid = Config::get("constants.akola_sender_id"); #senderid of the deparment
            $entityID = Config::get("constants.akola_entity_id"); #entityid of the deparment
            $url = Config::get("constants.akola_url");
            $message = $message; #message content

            $data = array(
                "UserID" => trim($username),
                "Password" => trim($password),
                "SenderID" => trim($senderid),
                "Phno" => trim($mobileno),
                "Msg" => trim($message),
                "EntityID" => trim($entityID),
                "TemplateID" => $templateid,
            );

            $fields = '';
            foreach ($data as $key => $value) {
                $fields .= $key . '=' . ($value) . '&';
            }
            $url = $url . (rtrim($fields, '&'));
            $result = Http::post($url);
            $responseBody = json_decode($result->getBody(), true);
            if (isset($responseBody["Status"]) && strtoupper($responseBody["Status"]) == 'OK') {
                $response = ['response' => true, 'status' => 'success', 'msg' => $responseBody["Response"]["Message"] ?? ""];
            } else {
                $response = ['response' => false, 'status' => 'failure', 'msg' => $responseBody["Response"]["Message"] ?? ""];
            }


            return $response;
        } else {
            if ($templateid == NULL)
                $response = ['response' => false, 'status' => 'failure', 'msg' => 'Template Id is required'];
            else
                $response = ['response' => false, 'status' => 'failure', 'msg' => 'Invalid Mobile No.'];
            return $response;
        }
    }
}
