<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Fleet;
use Illuminate\Support\Facades\File;

class FleetsController extends Controller
{
    public function index(Request $request)
    {
        // query untuk mengambil data armada dengan mengurutkan berdasarkan created_at
        // dan jika q tidak kosong
        $fleets = Fleet::orderBy('created_at', 'DESC')->when($request->q, function ($fleets) use ($request) {
            // maka fungsi filter berdasarkan plat nomor akan dijalankan
            $fleets->where('plate_number', $request->plate_number);
        })->paginate(10); //load 10 data perhalaman
        return response()->json([
            'status' => 'success',
            'data' => $fleets
        ]);
    }

    public function store(Request $request)
    {
        // membuat validasi data yang diterima
        $this->validate($request, [
            'plate_number' => 'required|string|unique:fleets,plate_number', // harus bersifat unik
            'type' => 'required',
            'photo' => 'required|image|mimes:jpg,jpeg,png' // file gambar yang diizinkan hanya jpg,jpeg, dan png
        ]);

        $user = $request->user(); // mengambil user yang sedang login
        $file = $request->file('photo'); // mengambil file yang diupload
        //membuat nama baru untuk file yang akan disimpan
        $filename = $request->plate_number . '-' . time() . '.' . $file->getClientOriginalExtension();
        // memindahkan file yang sudah diterima ke dalam folder public/fleet-photos dengan menggunakan nama baru yang sudah dibuat
        $file->move('fleet-photos', $filename);

        //simpan informasi data armadanya ke table fleets melalui model fleet
        Fleet::create([
            'plate_number' => $request->plate_number,
            'type' => $request->type,
            'photo' => $filename, // gunakan file yang sudah dibuat untuk mengenali gambar
            'user_id' => $user->id
        ]);
        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $fleet = Fleet::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $fleet
        ]);
    }

    public function update(Request $request, $id)
    {
        //buat validasi data yang akan diupdate
        $this->validate($request, [
            'plate_number' => 'required|string|unique:fleets,plate_number,' . $id, // unik kecuali data yang sedang diedit
            'type' => 'required',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png' //gambar boleh kosong
        ]);

        $fleet = Fleet::find($id);
        $filename = $fleet->photo; //simpan ama file gambar yang sebelumnya

        //jika file gambarnya ingin diperbaharui
        if ($request->hasFile('photo')) {
            //maka lakukan hal yang sama seperti sebelumnya menyimpan gambar
            $file = $request->file('photo');
            $filename = $request->plate_number . '-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move('fleet-photos', $filename);

            File::delete(base_path('public/fleet-photos/' . $fleet->photo)); //hapus gambar yang lama
        }

        // dan perbaharui data yang di databse
        $fleet->update([
            'plate_number' => $request->plate_number,
            'type' => $request->type,
            'photo' => $filename
        ]);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id){
        $fleet = Fleet::find($id); // ambil data berdasarkan id
        File::delete(base_path('public/fleet-photos/' . $fleet->photo)); //hapus file gambar
        $fleet->delete(); // hapus data dari database
        return response()->json(['status' => 'success']);
    }
}
