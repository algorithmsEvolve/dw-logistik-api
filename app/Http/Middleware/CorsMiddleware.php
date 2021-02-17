<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next){
        // rule headersnya harus kita set secara spesifik seperti ini
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ];

        // tapi jika method yang masuk adalah options
        if($request->isMethod('OPTIONS')){
            // maka kita kembalikan bahwa method tersebut adalah options
            return response()->json('{"method": "OPTIONS"}', 200, $headers);
        }

        // selain itu, kita akan meneruskan response seperti biasa dengan mengikutsertakan headers yang sudah ditetapkan
        $response = $next($request);
        foreach($headers as $key => $row){
            $response->header($key, $row);
        }

        return $response;
    }
}