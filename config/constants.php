<?php

/**
 * | Created On-08-06-2023 
 * | Author-Anshu Kumar
 * | Created for - Symbolic Constants Used On various Functions and classes
 */
return [
    "MICROSERVICES_APIS"   => env('MICROSERVICES_APIS'),
    "CUSTOM_RELATIVE_PATH" => "Uploads/Custom",
    "DOC_URL"              => env('DOC_URL'),

    #_Module Constants
    "PROPERTY_MODULE_ID"      => 1,
    "WATER_MODULE_ID"         => 2,
    "TRADE_MODULE_ID"         => 3,
    "SWM_MODULE_ID"           => 4,
    "ADVERTISEMENT_MODULE_ID" => 5,
    "WATER_TANKER_MODULE_ID"  => 11,


    "USER_TYPE" => [
        "Admin",
        "Employee",
        "JSK",
        "TC",
        "TL",
        "Pseudo User",
    ],

    #_Credentials for sms from env
    "akola_user_name" => env('SMS_USER_NAME'),
    "akola_password"  => env('SMS_PASSWORD'),
    "akola_sender_id" => env('SMS_SENDER_ID'),
    "akola_entity_id" => env('SMS_ENTITY_ID'),
    "akola_url"       => env('SMS_URL'),
    "sms_test"       => env('SMS_TEST',false),

];
