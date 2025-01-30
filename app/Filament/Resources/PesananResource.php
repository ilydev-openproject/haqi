<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Toko;
use Filament\Tables;
use App\Models\Divisi;
use App\Models\Produk;
use App\Models\History;
use App\Models\Pesanan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use League\Uri\Idna\Option;
use Filament\Actions\EditAction;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\PesananResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\Section as SectionList;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\PesananResource\RelationManagers;
use App\Filament\Resources\PesananResource\Pages\EditPesanan;
use App\Filament\Resources\PesananResource\Pages\ListPesanans;
use App\Filament\Resources\PesananResource\Pages\CreatePesanan;

class PesananResource extends Resource
{
    protected static ?string $model = Pesanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Penjualan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('resi')
                            ->label('Nomor Resi')
                            ->required(),
                        Select::make('toko_id')
                            ->label('Toko')
                            ->relationship('toko', 'nama_toko')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('harga_jual')
                            ->required(),
                        Select::make('ekspedisi')
                            ->label('Ekspedisi')
                            ->options([
                                'JNE' => 'JNE',
                                'J&T' => 'J&T',
                                'SAP' => 'SAP',
                                'LEX' => 'LEX',
                                'NINJA' => 'NINJA',
                                'TIKI' => 'TIKI',
                                'SICEPAT' => 'SICEPAT',
                            ])
                            ->required()
                            ->searchable()
                            ->required(),
                    ])
                    ->columnSpan(1),
                Section::make()
                    ->schema([
                        TextInput::make('wa_customer')
                            ->label('WA Customer')
                            ->required(),
                        RichEditor::make('alamat_customer')
                            ->label('Alamat Customer')
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('file_resi')
                            ->label('File Resi')
                            ->disk('resi')
                            ->uploadingMessage('Upload file resi...')
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable()
                            ->openable()
                            ->required(),
                    ])

                    ->columnspan(3),
            ])
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_pesanan')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('resi')
                    ->label('No. Resi')
                    ->sortable(),
                TextColumn::make('project.produk.nama_produk')
                    ->label('Type Produk'),
                TextColumn::make('project.customer_name')
                    ->label('Nama Customer'),
                TextColumn::make('toko.nama_toko')
                    ->label('Toko'),
                TextColumn::make('ekspedisi')
                    ->label('Ekspedisi'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'new',            // Warna biru untuk status "new"
                        'warning' => 'produksi',       // Warna kuning untuk status "produksi"
                        'info' => 'packing',           // Warna biru muda untuk status "packing"
                        'success' => 'ready_to_ship',  // Warna hijau untuk status "ready_to_ship"
                        'gray' => 'dikirim',           // Warna abu-abu untuk status "dikirim"
                        'purple' => 'sampai',          // Warna ungu untuk status "sampai"
                        'danger' => 'retur',           // Warna merah untuk status "retur"
                    ])
                    ->icons([
                        'heroicon-o-bolt' => 'new',                 // Ikon petir untuk status "new"
                        'heroicon-o-cog' => 'produksi',             // Ikon gear untuk status "produksi"
                        'heroicon-o-archive-box' => 'packing',      // Ikon kotak untuk status "packing"
                        'heroicon-o-truck' => 'ready_to_ship',      // Ikon truk untuk status "ready_to_ship"
                        'heroicon-o-paper-airplane' => 'dikirim',   // Ikon pesawat kertas untuk status "dikirim"
                        'heroicon-o-check' => 'sampai',             // Ikon centang untuk status "sampai"
                        'heroicon-o-arrow-uturn-left' => 'retur',   // Ikon panah balik untuk status "retur"
                    ])
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('updateStatus')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                        ->form([
                            Select::make('status')
                                ->label('Pilih Status')
                                ->options(function ($record) {
                                    $options = [];

                                    // Alur status yang diizinkan
                                    if ($record->status === 'new') {
                                        $options['produksi'] = 'Produksi'; // Admin bisa memindahkan ke Produksi
                                    }
                                    if ($record->status === 'produksi') {
                                        $options['packing'] = 'Packing'; // Produksi bisa memindahkan ke Packing
                                    }
                                    if ($record->status === 'packing') {
                                        $options['ready_to_ship'] = 'Siap Kirim'; // Packing bisa memindahkan ke Ready to Ship
                                    }
                                    if ($record->status === 'ready_to_ship') {
                                        $options['dikirim'] = 'Dikirim'; // Admin bisa memindahkan ke Dikirim
                                    }
                                    if ($record->status === 'dikirim') {
                                        $options['sampai'] = 'Sampai'; // Admin bisa memindahkan ke Sampai
                                    }
                                    if ($record->status === 'sampai') {
                                        $options['retur'] = 'Retur'; // Admin bisa memindahkan ke Retur
                                    }

                                    return $options;
                                })
                                ->preload()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            $pesanan = Pesanan::find($record->id_pesanan);

                            if (!$pesanan) {
                                return;
                            }

                            $divisiId = auth()->user()->divisi_id;
                            $pesanan->updateStatus($data['status'], $divisiId);
                        })
                        ->visible(fn($record) => match (auth()->user()->divisi->nama_divisi) {
                            'Admin' => in_array($record->status, ['new', 'ready_to_ship', 'dikirim', 'sampai']),
                            'Produksi' => $record->status === 'produksi',
                            'Packing' => $record->status === 'packing',
                            default => 'new',
                        }),
                    ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPesanans::route('/'),
            'create' => Pages\CreatePesanan::route('/create'),
            'edit' => Pages\EditPesanan::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        // Mengambil riwayat status terkait pesanan
        $histories = History::where('pesanan_id', $infolist->getRecord()->id_pesanan)
            ->orderBy('created_at', 'asc')
            ->get();
        return $infolist
            ->schema([
                TextEntry::make('kode_pesanan')
                    ->label('Kode Pesanan'),
                TextEntry::make('project.customer_name')
                    ->label('Nama Customer'),
                SectionList::make('Riwayat Status')
                    ->label('Daftar Riwayat Status')
                    ->schema(
                        $histories->sortBy('created_at')->map(function ($history) {
                            $statusAwal = ucfirst(str_replace('_', ' ', $history->status_awal));
                            $statusAkhir = ucfirst(str_replace('_', ' ', $history->status_akhir));
                            $updatedBy = $history->user ? $history->user->name : 'User Tidak Ditemukan';
                            $updatedAt = $history->created_at->locale('id')->isoFormat('D MMM YYYY - HH:mm'); // Format tanggal Indonesia

                            // Menyusun pesan berdasarkan status_awal dan status_akhir
                            $statusMessage = match (true) {
                                ($history->status_awal === 'new' && $history->status_akhir === 'new') => "Pesanan dibuat oleh {$updatedBy}",
                                ($history->status_awal === 'new' && $history->status_akhir === 'produksi') => "Pesanan diserahkan ke produksi",
                                ($history->status_awal === 'produksi' && $history->status_akhir === 'packing') => "Pesanan diserahkan ke packing",
                                ($history->status_awal === 'packing' && $history->status_akhir === 'ready_to_ship') => "Pesanan siap dikirim",
                                ($history->status_awal === 'ready_to_ship' && $history->status_akhir === 'dikirim') => "Pesanan sedang dikirim",
                                ($history->status_awal === 'dikirim' && $history->status_akhir === 'sampai') => "Pesanan telah sampai ke pelanggan",
                                ($history->status_awal === 'sampai' && $history->status_akhir === 'retur') => "Pesanan dikembalikan (retur)",
                                default => "Status tidak diketahui",
                            };

                            // Menentukan warna badge berdasarkan status_akhir
                            $badgeColor = match ($history->status_akhir) {
                                'new' => 'primary',         // Biru
                                'produksi' => 'warning',    // Kuning
                                'packing' => 'info',        // Biru Muda
                                'ready_to_ship' => 'success', // Hijau
                                'dikirim' => 'gray',        // Abu-abu
                                'sampai' => 'stabilo',      // Hijau lebih gelap
                                'retur' => 'danger',        // Merah
                                default => 'secondary',     // Warna default
                            };

                            // Menentukan ikon berdasarkan status_akhir
                            $icon = match ($history->status_akhir) {
                                'new' => 'heroicon-o-bolt',                 // Petir
                                'produksi' => 'heroicon-o-cog',             // Gear (Produksi)
                                'packing' => 'heroicon-o-archive-box',      // Kotak (Packing)
                                'ready_to_ship' => 'heroicon-o-truck',      // Truk (Siap Kirim)
                                'dikirim' => 'heroicon-o-paper-airplane',   // Pesawat kertas (Dikirim)
                                'sampai' => 'heroicon-o-check-circle',      // Ceklis (Sampai)
                                'retur' => 'heroicon-o-arrow-uturn-left',   // Panah balik (Retur)
                                default => 'heroicon-o-question-mark-circle', // Ikon tidak diketahui
                            };

                            return Grid::make()
                                ->schema([
                                    // Kolom pertama: Tanggal dan status awal
                                    TextEntry::make('history_' . $history->id)
                                        ->label('')
                                        ->getStateUsing(fn() => "{$updatedAt} - {$statusMessage}")
                                        ->columnSpan(9), // 9 dari 12 kolom

                                    // Kolom kedua: Status akhir dengan badge & ikon
                                    TextEntry::make('status_akhir_' . $history->id)
                                        ->label('')
                                        ->getStateUsing(fn() => "{$statusAkhir}")
                                        ->badge()
                                        ->color($badgeColor)
                                        ->icon($icon)
                                        ->columnSpan(3)
                                        ->alignEnd(),
                                ])
                                ->columns(12)
                                ->columnSpanFull();
                        })->all()
                    ),
            ]);
    }
}
