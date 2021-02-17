<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Admin nih',
            'identity_id' => '123456789',
            'gender' => 1,
            'address' => 'Jl. Lingkar Luar Barat',
            'photo' => 'gadagambar.png', //note: tidak ada gambar
            'email' => 'admin@testweb.id',
            'password' => app('hash')->make('secret'),
            'phone_number' => '085439243734',
            'api_token' => Str::random(40),
            'role' => 0,
            'status' => 1
        ]);
    }
}
