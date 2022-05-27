<?php

class CSVIntegrator {

    private static $instance = null;
    protected $csvURL;

    private function __construct($csvURL) {
        $this->csvURL = $csvURL;
    }


    /**
     * Singleton
     */
    protected function __clone() { }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance($csvURL)
    {
        if (self::$instance == null)
        {
        self::$instance = new CSVIntegrator($csvURL);
        }
        return self::$instance;
    }

    
    /**
     * Get CSV file, parse it to array and return
     */
    public function getCSVAsArray($mapping, $count = false) {
        $csvRaw = $this->makeGetCurlCall($this->csvURL);
        $csvArray = [];
        $delimiter = ',';
        $lineBreak = "\n";
        $rows = str_getcsv($csvRaw, $lineBreak); // Parses the rows. Treats the rows as a CSV with \n as a delimiter
        $counter = 0;
        foreach ($rows as $row) {
            $rowRawArray = str_getcsv($row, $delimiter);
            $rowTmpObj = array();
            for ($i=0; $i < count($mapping); $i++) { 
                $rowTmpObj[$mapping[$i]] = $rowRawArray[$i];
            }
            if(!isset($mapping['image'])){
                $rowTmpObj['image'] = $rowTmpObj['_sku'].".jpg";
            }
            $csvArray[] = $rowTmpObj;
            if($count != false){
                $counter++;
                if($counter == $count){
                    break;
                }
            }
        }
        print_r($csvArray);die();
        return $csvArray;
    }



    /**
     * CURL GET & POST CALLS
     */
    private function makeGetCurlCall($_url, $_data = [], $_headers = []) {
        $globalHeaders = $this->globalHeaders ? $this->globalHeaders : [];
        $_headers = array_merge($_headers, $globalHeaders);
        
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $_url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($cURLConnection, CURLOPT_VERBOSE, true );
        curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($cURLConnection);

        $http_code = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        // Check HTTP status code
        if (!curl_errno($cURLConnection)) {
            switch ($http_code) {
                case 200:  # OK
                    
                    break;
                default:
                    echo 'Unexpected HTTP code: ', $http_code, "\n";
            }
        }

        curl_close($cURLConnection);
    
        return $response;
    }

    private function makePostCurlCall($_url, $_data = [], $_headers = []) {
        $globalHeaders = $this->globalHeaders ? $this->globalHeaders : [];
        $_headers = array_merge($_headers, $globalHeaders);

        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_URL, $_url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($cURLConnection, CURLOPT_POST, 1);
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($_data));
        curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($cURLConnection);

        $http_code = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        // Check HTTP status code
        if (!curl_errno($cURLConnection)) {
            switch ($http_code) {
                case 200:  # OK
                    break;
                default:
                    echo 'Unexpected HTTP code: ', $http_code, "\n";
            }
        }

        curl_close($cURLConnection);
    
        return json_decode($response);
    }
    
}