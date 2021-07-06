<?php

class SimpleJsonRequest extends Cache
{

    private static function makeRequest(string $method, string $url, array $parameters = null, array $data = null)
    {

        $cache = self::getCache($method, $url, $parameters, $data);

        if( $cache != "" ){
            return $cache;            
        }

        $opts = [
            'http' => [
                'method'  => $method,
                'header'  => 'Content-type: application/json',
                'content' => $data ? json_encode($data) : null
            ]
        ];

        $url .= ($parameters ? '?' . http_build_query($parameters) : '');
        $response = file_get_contents($url, false, stream_context_create($opts));

        self::setCache($method, $url, $parameters, $data, $response);

        return $response;

    }

    public static function get(string $url, array $parameters = null)
    {
        return json_decode(self::makeRequest('GET', $url, $parameters));
    }

    public static function post(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('POST', $url, $parameters, $data));
    }

    public static function put(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('PUT', $url, $parameters, $data));
    }   

    public static function patch(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('PATCH', $url, $parameters, $data));
    }

    public static function delete(string $url, array $parameters = null, array $data = null)
    {
        return json_decode(self::makeRequest('DELETE', $url, $parameters, $data));
    }
}

class Cache
{

    /**
     *  $cacheTime int Time in seconds to expire the cache
     *  $path string path to save cache archives, important to be sure 
     *               about de dir and permissions to managment them  
     */
    private static $cacheTime = 20;
    private static $path = './caches/';

    private static function createStorage(string $index, string $value )
    {

        if( $value != "" ){

            $file = fopen( self::$path. sha1($index) . ".txt","w");
            fwrite( $file, $value );
            fclose($file);

        }

    }


    private static function getStorage(string $index)
    {

        $response = "";
        $chaceName = sha1( $index ) . ".txt";

        if( file_exists( self::$path . $chaceName ) ){

            $dateNowLessTimer = date('YmdHis', strtotime( date('YmdHis') . ' -' . self::$cacheTime . ' seconds'));
            
            if( $dateNowLessTimer >  date ("YmdHis", filemtime(self::$path . $chaceName)) ){
                unlink( self::$path . $chaceName );
            } else {

                $handle = fopen ( self::$path . $chaceName, "r" );
                $response = fread ($handle, filesize( self::$path . $chaceName ) );
                fclose ($handle);

            }

        }

        return $response;

    }

    public static function getCache(string $method, string $url, array $parameters=null, $data=null)
    {

        $cacheIndex = json_encode([
            'method' => $method,
            'url' => $url,
            'parameters' => $parameters,
            'data' => $data
        ]);

        return self::getStorage( $cacheIndex );

    }

    public static function setCache(string $method, string $url, array $parameters=null, $data=null, $response=null)
    {

        $cacheIndex = json_encode([
            'method' => $method,
            'url' => $url,
            'parameters' => $parameters,
            'data' => $data
        ]);

        self::createStorage( $cacheIndex, $response );

    }

}

$resp = new SimpleJsonRequest();

// Examples
var_dump( $resp::get('https://api.duckduckgo.com/?q=!imdb+rushmore&format=json&pretty=1&no_redirect=1') );
var_dump( $resp::get('https://api.duckduckgo.com/?q=cache&format=json&pretty=1&no_redirect=1') );
var_dump( $resp::get('https://api.duckduckgo.com/?q=php&format=json&pretty=1&no_redirect=1') );
var_dump( $resp::get('https://api.duckduckgo.com/?q=node&format=json&pretty=1&no_redirect=1') );
