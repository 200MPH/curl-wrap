<?php

/**
 * Curl client
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 */

namespace thm\curl;

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
         $this->setTimeout(5);
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();   
    }
    
    /**
     * Set CURL timeout.
     * Default value is set to 5 seconds, when object initialized.
     * 
     * @param int $seconds
     * @see Curl::__construct()
     * @return Curl
     */
    public function setTimeout(int $seconds)
    {
        return $this->setopt(CURLOPT_TIMEOUT, $seconds);
    }
    
    /**
     * Set cURL option
     * 
     * @param const $curlOptionName
     * @param mixed $curlOptionValue
     * 
     * @return Curl
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
     * Set POST parameters.
     * This function will let you pass parameters fast and easy, 
     * so no need to set additional CURL options.
     * 
     * @param array $params
     * @return Curl
     */
    public function setParameters(array $params)
    {
        return $this->setopt(CURLOPT_POSTFIELDS, http_build_query($params));
    }
    
    /**
     * Get CURL error number
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
     * Get CURL error string
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
     * Close CURL session
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
     * CURL execute - get results
     * 
     * @param bool $return [optional] Set option CURLOPT_RETURNTRANSFER. Default set to true.
     * @return mixed <b>TRUE</b> on success or <b>FALSE</b> on failure. However, if the <b>CURLOPT_RETURNTRANSFER</b>
     * option is set, it will return the result on success, <b>FALSE</b> on failure.
     */
    public function exec(bool $return = true)
    {
        if($this->curlHandle !== false) {   
            if($return === true) {
                $this->setopt(CURLOPT_RETURNTRANSFER, 1);
            }
            return curl_exec($this->curlHandle);
        } else {
            $this->throwNotInitializedException();
        }
    }
    
    /**
     * Alias for exec()
     * 
     * @see Curl::exec()
     */
    public function call()
    {
        return $this->exec();
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
