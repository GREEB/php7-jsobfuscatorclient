<?php

namespace n05x;

class JSObfuscatorClient
{
    private $apikey = '';
    private $apipass = '';

    private $projectname = '';

    private $baseuri = 'https://service.javascriptobfuscator.com';
    private $apiuri = 'HttpApi.ashx';

    private $log = [];

    // User facing function to initialize the library
    public function __construct($apikey, $apipass, $projectname = 'unnamed')
    {
        if (!$apikey) {
            throw new \Exception('Missing account API key');
        }
        if (!$apipass) {
            throw new \Exception('Missing account API password');
        }
        $this->apikey = $apikey;
        $this->apipass = $apipass;
        $this->projectname = $projectname;
    }

    // Return our log messages of requests and responses for debugging
    public function logs()
    {
        return $this->log;
    }

    public function dump($var)
    {
        ob_start();
        print_r($var);

        return ob_get_clean();
    }

    /*
        determine if a string is valid json to decode, return bool
    */
    public static function isJson($string)
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /*
        Check the last JSON encode or decode error and throw exceptions if there is a problem
    */
    public static function testJsonError()
    {
        // handle possible json errors and throw exceptions
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                throw new \Exception('The maximum stack depth has been exceeded');
            case JSON_ERROR_STATE_MISMATCH:
                throw new \Exception('Invalid or malformed JSON');
            case JSON_ERROR_CTRL_CHAR:
                throw new \Exception('Control character error, possibly incorrectly encoded');
            case JSON_ERROR_SYNTAX:
                throw new \Exception('Syntax error, malformed JSON');
            case JSON_ERROR_UTF8:
                throw new \Exception('Malformed UTF-8 characters, possibly incorrectly encoded');
            case JSON_ERROR_RECURSION:
                throw new \Exception('One or more recursive references in the value to be encoded');
            case JSON_ERROR_INF_OR_NAN:
                throw new \Exception('One or more NAN or INF values in the value to be encoded');
            case JSON_ERROR_UNSUPPORTED_TYPE:
                throw new \Exception('A value of a type that cannot be encoded was given');
            default:
                throw new \Exception('Unknown JSON error occured');
        }

        return false;
    }

    /*
        encode data into json and throw exceptions if there is a problem
    */
    public static function encodeJson($data)
    {
        // decode json into an array
        $result = json_encode($data, true);
        // handle possible json errors and throw exceptions
        self::testJsonError();

        return $result;
    }

    /*
        decode json into an array and throw exceptions if there is a problem
    */
    public static function decodeJson($string)
    {
        // decode json into an array
        $result = json_decode($string, true);
        // handle possible json errors and throw exceptions
        self::testJsonError();

        return $result;
    }

    // Internal function to post json back to the API
    protected function httpPostJson($uri, $body)
    {
        $this->log[] = [
                        'request' => [
                                    'method' => 'POST',
                                    'uri'    => $uri,
                                    'body'   => $body,
                                    ],
                        ];

        $response = \Httpful\Request::post($uri)
                                                ->sendsAndExpects('text/json')
                                                ->parseWith(function ($body) {
                                                    return self::decodeJson($body);
                                                })
                                                ->body(self::encodeJson($body))
                                                ->send()
                                                ->body;
        $this->log[count($this->log) - 1]['response'] = $response;

        if ($response['Type'] != 'Succeed') {
            throw new \Exception('');
        }

        return $response;
    }

    // Obfuscates a project of files
    public function ObfuscateScript($scriptname, $scriptcode)
    {
        // build our postable request body
        $request = [
                       'APIKey'        => $this->apikey,
                       'APIPwd'        => $this->apipass,
                       'Name'          => $this->projectname,
                       'ReplaceNames'  => true,
                       'EncodeStrings' => true,
                       'MoveStrings'   => true,
                       'Items'         => [
                                              [
                                                  'FileName' => $scriptname,
                                                  'FileCode' => $scriptcode,
                                              ],
                                          ],
                   ];
        $uri = $this->baseuri.'/'.$this->apiuri;
        $result = $this->httpPostJson($uri, $request);

        return $result['Items'][0]['FileCode'];
    }
}
