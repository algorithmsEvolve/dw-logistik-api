<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        //query untuk mengambil data dari table users dan di-load 10 data per halaman
        $users = User::orderBy('created_at', 'desc')->when($request->q, function($users) use($request){
            $users = $users->where('name', 'LIKE', '%' . $request->q . '%');
        })->paginate(10);
        //kembalikan response berupa json dengan format
        //status = success
        //data = data users dari hasil query
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        //bagian baru untuk validation
        $this->validate($request, [
            'name' => 'required|string|max:50',
            'identity_id' => 'required|string|unique:users', //unique berarti data ini tidak boleh sama di dalam table users
            'gender' => 'required',
            'address' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone_number' => 'required|string',
            'role' => 'required',
            'status' => 'required'
        ]);

        //defaultnya filename adalah null karena user yang tipenya bukan driver, bisa mengosongkan foto diri
        $filename = null;
        //kemudian cek jika ada file yang dikirimkan
        if ($request->hasFile('photo')) {
            //maka generate nama untuk file tersebut dengan format string random+email
            $filename = Str::random(5) . $request->email . '.jpg';
            $file = $request->file('photo');
            $file->move(base_path('public/images'), $filename); //simpan file tersebut ke dalam public images
        }

        //simpan data user ke dalam table users menggunkana model user
        User::create([
            'name' => $request->name,
            'identity_id' => $request->identity_id,
            'gender' => $request->gender,
            'address' => $request->address,
            'photo' => $filename, //untuk foto kita gunakan value dari variable filename
            'email' => $request->email,
            'password' => app('hash')->make($request->password), //passwordnya kita encrypt
            'phone_number' => $request->phone_number,
            //'api_token' => 'test', //bagian ini  harusnya kosong karena akan terisi jika user login
            'role' => $request->role,
            'status' => $request->status
        ]);

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        //mengambil data berdasarkan id
        $user = User::find($id);
        //kemudian kirim datanya dalam bentuk json
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        //bagian baru untuk validation
        $this->validate($request, [
            'name' => 'required|string|max:50',
            'identity_id' => 'required|string|unique:users,identity_id' . $id, //unique berarti data ini tidak boleh sama di dalam table users
            'gender' => 'required',
            'address' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png',
            'email' => 'required|email|unique:users,email' . $id,
            'password' => 'nullable|min:6',
            'phone_number' => 'required|string',
            'role' => 'required',
            'status' => 'required'
        ]);

        $user = User::find($id); //get data user

        //jika password yang dikirimkan user kosong, maka dia tidak ingin mengganti password, maka kita akan mengambil password saat ini untuk disimpan kembali
        //jika tidak kosong, maka kita encrypt password yang baru
        $password = $request->password != '' ? app('hash')->make($request->password) : $user->password;

        //logic yang sama adalah default dari $filename adalah nama file dari database
        $filename = $user->photo;

        //jika ada file gambar yang dikirim
        if ($request->hasFile('photo')) {
            //maka kita generate nama dan simpan file baru tersebut
            $filename = Str::random(5) . $user->email . '.jpg';
            $file = $request->file('photo');
            $file->move(base_path('public/images'), $filename);
            //hapus file lama
            unlink(base_path('public/images/' . $user->photo));
        }

        //kemudian perbarui data user
        $user->update([
            'name' => $request->name,
            'identity_id' => $request->identity_id,
            'gender' => $request->gender,
            'address' => $request->address,
            'photo' => $filename,
            'password' => $password,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'status' => $request->status
        ]);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user->photo) {
            unlink(base_path('public/images/' . $user->photo));
        }
        $user->delete();

        return response()->json(['status' => 'success']);
    }

    public function login(Request $request)
    {
        // validasi inputan user
        // dengan ketentuan email harus ada di tabel users dan password min 6
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6'
        ]);

        // kita cari user berdasarkan email
        $user = User::where('email', $request->email)->first();
        // jika data user ada
        // kita check password user apakah sudah sesuai atau belum
        // untuk membandingkan encrypted password dengan plain text, kita bisa menggunakan facade check
        if ($user && Hash::check($request->password, $user->password)) {
            $token = Str::random(40); //generate token baru
            $user->update(['api_token' => $token]); //update user terkait
            //dan kembalikan tokennya untuk digunakan pada client
            return response()->json([
                'status' => 'success',
                'data' => $token
            ]);
        }
        //jika tidak sesuai berikan response error
        return response()->json(['status' => 'error']);
    }

    public function sendResetToken(Request $request)
    {
        //validasi email untuk memastikan bahwa emailnya sudah ada
        $this->validate($request, [
            'email' => 'required|email|exists:users'
        ]);

        //get data user berdasarkan email tersebut
        $user = User::where('email', $request->email)->first();
        //lalu generate tokennya
        $user->update(['reset_token' => Str::random(40)]);

        //kirim token via email sebagai otentikasi kepemilikan
        Mail::to($user->email)->send(new ResetPasswordMail($user));

        return response()->json([
            'status' => 'success',
            'data' => $user->reset_token
        ]);
    }

    public function verifyResetPassword(Request $request, $token)
    {
        // validasi password harus min 6
        $this->validate($request, [
            'password' => 'required|string|min:6'
        ]);

        // cari user berdasarkan token yang diterima
        $user = User::where('reset_token', $token)->first();
        // jika datanya ada
        if ($user) {
            // update password terkait
            $user->update(['password' => app('hash')->make($request->password)]);
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error']);
    }

    public function getUserLogin(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user(); // get user yang sedang login
        $user->update(['api_token' => null]); //update valuenya menjadi null
        return response()->json(['status' => 'success']);
    }
}
