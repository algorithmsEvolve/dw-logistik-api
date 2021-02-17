<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;

class CategoryController extends Controller
{
    public function index(Request $request){
        // query menggunakan model category, dimana ketika parameter q tidak kosong
        $categories = Category::when($request->q, function($categories) use($request){
            // maka akan dilakukan filter berdasarkan name
            $categories->where('name', 'LIKE', '%' . $request->q . '%');
        })->orderBy('created_at', 'DESC')->paginate(10); // dan diorder berdasarkan data terbaru
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function store(Request $request){
        // validasi data yang diterima
        $this->validate($request, [
            'name' => 'required|string|unique:categories,name', // name bersifat unik
            'description' => 'nullable|string|max:150'
        ]);

        // simpan data ke table categories menggunakan mass assignment eloquent
        Category::create([
            'name' => $request->name,
            'description' => $request->description
        ]);
        return response()->json(['status' => 'success']);
    }

    public function edit($id){
        $category = Category::find($id); //mengambil data berdasarkan id
        return response()->json([
            'status' => 'success', 
            'data' => $category //dan mengirimkan response berupa data yang diambil dari database
        ]);
    }

    public function update(Request $request, $id){
        //validasi data
        $this->validate($request, [
            // dimana name bersifat unik tapi dikecualikan untuk id yang sedang diedit
            'name' => 'required|string|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:150'
        ]);

        $category = Category::find($id); // ambil data berdasarkan id
        // dan perbarui data
        $category->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id){
        $category = Category::find($id); // mengambil data berdasarkan id
        $category->delete(); // menghapus data
        return response()->json(['status' => 'success']);
    }
}
