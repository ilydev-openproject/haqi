<?php

namespace App\Models;

use App\Models\Pesanan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Produk extends Model
{
    protected $table = 'produks';
    protected  $primaryKey = 'id_produk';
    protected $guarded = [];

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_produk');
    }

    public function project()
    {
        return $this->hasMany(Pesanan::class, 'id_produk', 'id_produk');
    }

    public function store(Request $request)
    {
        // Simpan data
        Produk::create($request->all());

        // Redirect ke halaman daftar produk
        return redirect('/produk'); // Ganti '/produk' dengan URL yang sesuai
    }
}
