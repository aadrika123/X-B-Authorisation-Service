<?php

namespace App\BLL;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Promise;

/**
 * | Author- Anshu Kumar
 * | Created On-11-08-2023 
 * | Status-Closed
 * | Logic Layer for the api gateway Service
 */

class ApiGatewayBll
{
    /**
     * | Initializations
     */
    public function __construct()
    {
    }

    /**
     * | Get api response
     */
    public function getApiResponse($req)
    {
        $client = new Client();
        // Converting environmental variables to Services
        $baseURLs = Config::get('constants.MICROSERVICES_APIS');
        if (collect($baseURLs)->isEmpty())
            throw new Exception("Microservices Base Urls Not Configured");
        $services = json_decode($baseURLs, true);
        return $services;
        // Sending to Microservices
        $segments = explode('/', $req->path());
        $service = $segments[1];
        if (!array_key_exists($service, $services))
            throw new Exception("Service Not Available");

        $url = $services[$service];
        $ipAddress = getClientIpAddress();

        $authFields = [
            'token' => $req->bearerToken(),
            'ipAddress' => $ipAddress
        ];

        if ($req->authRequired) {                           // Auth Required fields
            $authFields = array_merge([
                'auth' => authUser(),
                'token' => $req->bearerToken(),
                'currentAccessToken' => $req->user()->currentAccessToken(),
                'apiToken' => $req->user()->currentAccessToken()->token
            ]);
        }

        $req = $req->merge($authFields);                    // Merging authenticated fields

        $method = $req->method();
        $promises = [];
        $asyncMethod = in_array($method, ['POST', 'post']) ? 'postAsync' : 'getAsync';

        if (isset($req->header()['content-type']) && preg_match('/multipart/i', $req->header()["content-type"][0]) && $_FILES) {
            $promise = $client->$asyncMethod($url . $req->getRequestUri(), [                // for Multipart
                'multipart' => $this->prepareMultipartData($req),
                [
                    'headers' => $req->header()                         // Attach all headers
                ]
            ]);
        } else {
            $promise = $client->$asyncMethod(
                $url . $req->getRequestUri(),
                [
                    'json' => $req->all(),
                    [
                        'headers' => $req->header()                         // Attach all headers
                    ]
                ]
            );
        }
        // Create an async HTTP POST request
        $promises[] = $promise;
        // Wait for the promise to complete
        $responses = Promise\Utils::settle($promises)->wait();
        // Process the response
        $response = $responses[0];

        if ($response['state'] === Promise\PromiseInterface::FULFILLED) {
            $apiResponse = $response['value']->getBody()->getContents();    // Process the response body as needed
            return json_decode($apiResponse, true);
        } else {
            $apiResponse = $response['reason']->getMessage();            // Handle the error message as needed
            throw new Exception($apiResponse);
        }
    }


    /**
     * | Prepare Multipart Data For Multipart Forms
     */
    public function prepareMultipartData(Request $req, $prefix = null)
    {
        $multipartData = [];

        foreach ($req->all() as $key => $value) {
            $currentKey = $prefix ? "{$prefix}[{$key}]" : $key;
            if (is_array($value)) {
                $value = new Request($value);
                $multipartData = array_merge($multipartData, $this->prepareMultipartData($value, $currentKey));
            } else {
                if ($value instanceof UploadedFile) {
                    $multipartData[] = [
                        'name' => $currentKey,
                        'contents' => fopen($value->getPathname(), 'r'),
                        'filename' => $value->getClientOriginalName(),
                    ];
                } else {
                    $multipartData[] = [
                        'name' => $currentKey,
                        'contents' => $value,
                    ];
                }
            }
        }
        return $multipartData;
    }
}
