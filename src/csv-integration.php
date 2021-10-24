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

    /** Validate end point and return pages */
    public function validateAllNewURI(){
        
        $firstPage = (array)$this->makeGetCurlCall($this->allNewsUri, [], []);
        //checking that the articles and totalPages keys exist
        if(array_key_exists('articles',$firstPage) && array_key_exists('totalPages',$firstPage)){
            return $firstPage['totalPages'];
        } else {
            //handling different malta site structue
            $firstPageArr = (array)json_decode($firstPage[0]);
            if(array_key_exists('articles',$firstPageArr) && array_key_exists('totalPages',$firstPageArr)){
                $this->mtStructure = true;
                return $firstPageArr['totalPages'];
            } else {
                return -1;
            }            
        }
    }
    /**
     * All Articles API Endpoints
     */
    public function getCSVAsJson() {
        $csv = $this->makeGetCurlCall($this->csvURL);
        return json_encode(str_getcsv($csv));
    }

    /**
     * Get single article by category and id 
     */
    public function getArticle($postId, $categoryId = ""){
        //replace placeholders with postId and categoryId where available
        
        $catEndpoint = str_replace("{{category}}",$categoryId,$this->singleArticleUri);
        $finalEndpoint = str_replace("{{postId}}",$postId,$catEndpoint);
        $article = (array)$this->makeGetCurlCall($finalEndpoint, [], []);
        if($this->mtStructure){
            $article = (array)json_decode($article[0]);
        }
        return $article;
    }

    /**
     * CURL GET & POST CALLS
     */
    private function makeGetCurlCall($_url, $_data = [], $_headers = []) {
        $_headers = array_merge($_headers, $this->globalHeaders);
        
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
        $_headers = array_merge($_headers, $this->globalHeaders);

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