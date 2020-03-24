<?php

/**
 * Curl client
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 */

namespace app\Vendor\Curl;

class Curl {
    
    /**
     * @var cURL
     */
    private $curlHandle = false;    
       
    /**
     * Init cURL
     * @param string $url
     */
    public function __construct(string $url)
    {
        
         $this->curlHandle = curl_init($url);
        
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        
        $this->close();
        
    }
    
    /**
     * Set cURL option
     * 
     * @param const $curlOptionName
     * @param mixed $curlOptionValue
     * 
     * @return void
     * @throws Exception CURL setopt() function returned False
     */
    public function setopt($curlOptionName, $curlOptionValue)
    {
        
        if($this->curlHandle !== false) {
            
            $status = curl_setopt($this->curlHandle, $curlOptionName, $curlOptionValue);
            
            if($status === false) {
                
                throw new CurlException('CURL setopt() function returned FALSE. Check parameter name is correct', CurlException::SETOP_FAIL);
                
            }
            
        } else {
            
            $this->throwNotInitializedException();
            
        }
        
    }
    
    /**
     * Get cURL error number
     * 
     * @return int Error number
     */
    public function getErrorNo()
    {
        
        if($this->curlHandle !== false) {
            
            return ( curl_errno($this->curlHandle) );
            
        } else {
            
            $this->throwNotInitializedException();
            
        }
        
    }
    
    /**
     * Get cURL error
     * 
     * @return string Error string
     */
    public function getError()
    {
        
        if($this->curlHandle !== false) {
            
            return ( curl_error($this->curlHandle) );
            
        } else {
            
            $this->throwNotInitializedException();
            
        }
        
    }
    
    /**
     * Close cURL session
     * 
     * @return void
     */
    public function close()
    {
        
        if($this->curlHandle !== false) {
            
            curl_close($this->curlHandle);
            $this->curlHandle = false;
            
        } 
        
    }
    
    /**
     * cURL execute
     * 
     * @return mixed <b>TRUE</b> on success or <b>FALSE</b> on failure. However, if the <b>CURLOPT_RETURNTRANSFER</b>
     * option is set, it will return
     * the result on success, <b>FALSE</b> on failure.
     */
    public function exec()
    {
        
        if($this->curlHandle !== false) {
            
            return curl_exec($this->curlHandle);
            
        } else {
            
            $this->throwNotInitializedException();
            
        }
        
    }
    
    /**
     * Throw Exception
     *  
     * @throws Exception cURL not initialized yet
     */
    private function throwNotInitializedException()
    {
        
        throw new CurlException('cURL not initialized yet', CurlException::CURL_INIT_FAIL);
        
    }
    
}
