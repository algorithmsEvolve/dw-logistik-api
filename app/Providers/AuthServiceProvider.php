<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->viaRequest('api', function($request){

            //jika ada header authorization yang dikirimkan
            if($request->header('Authorization')) {
                //make explode karena formatnya ada bearer + token
                //sedangkan yang diinginkan hanya tokennya saja
                $explode = explode(' ', $request->header('Authorization'));
                //kemudian find user berdasarkan token yang diterima
                return User::where('api_token', end($explode))->first();
            }
        });
    }
}
