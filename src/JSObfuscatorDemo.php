<?php

namespace n05x;

class JSObfuscatorDemo
{
    private $baseuri = 'https://javascriptobfuscator.com/Javascript-Obfuscator.aspx';

    private $log = [];

    // constructor sets mandatory fields
    public function __construct($cookiePath = '/tmp/curl.cookiejar')
    {
        // set our cookie path
        $this->cookiePath = $cookiePath;

        // setup curl handle
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_NOBODY, false);
        // set curl cookie options
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookiePath);
        // set curl user agent spoof
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_CERTINFO, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($this->curl, CURLOPT_VERBOSE      , true);
    }

    public function get($url, $referer = '')
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_REFERER, $referer);

        return $this->curl_exec();
    }

    public function post($url, $referer = '', $post = '')
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_REFERER, $url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);

        return $this->curl_exec();
    }

    // curl wrapper for fun
    public function curl_getinfo()
    {
        return curl_getinfo($this->curl);
    }

    public function curl_exec()
    {
        return curl_exec($this->curl);
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

    // Obfuscates a project of files
    public function ObfuscateScript($scriptname, $scriptcode)
    {
        $get = $this->get($this->baseurl);

        $post = [
            'UploadLib_Uploader_js'            => 1,
            'ctl00$breadcrumbs$uploader1'      => '',
            'ctl00$breadcrumbs$TextBox1'       => $scriptcode,
            'ctl00$MainContent$cbEncodeStr'    => 'on',
            'ctl00$MainContent$cbMoveStr'      => 'on',
            'ctl00$MainContent$cbReplaceNames' => 'on',
        ];

        $post = $this->post($url, $url, $post);

        $lines = explode("\n", $post);
        $regex = '/(var _0x.+)<\/textarea>/';
        $obfuscated = '';
        foreach ($lines as $line) {
            if (preg_match($regex, $line, $hits)) {
                $obfuscated = $hits[1];
                break;
            }
        }

        if (!$obfuscated) {
            throw new \Exception('Unable to identify the obfuscated script in the output');
        }

        $obfuscated = html_entity_decode($obfuscated);

        return $obfuscated;
    }
}
