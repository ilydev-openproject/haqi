<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pesanan extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $primaryKey = 'id_pesanan';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pesanan) {
            $pesanan->kode_pesanan = 'PSN-' . strtoupper(uniqid());
        });
    }


    public function produk()
    {
        return $this->belongsToMany(Produk::class, 'projects', 'id_produk', 'id_produk');
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function histories()
    {
        return $this->hasMany(History::class, 'pesanan_id');
    }

    public function updateStatus($newStatus, $divisiId)
    {
        $oldStatus = $this->status;

        // Update status pesanan
        $this->status = $newStatus;
        $this->save();

        // Catat riwayat di tabel histories
        History::create([
            'pesanan_id' => $this->id_pesanan,
            'status_awal' => $oldStatus,
            'status_akhir' => $newStatus,
            'user_id' => auth()->id(),
            'divisi_id' => $divisiId,
        ]);
    }
}
