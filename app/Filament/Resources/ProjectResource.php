<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Produk;
use App\Models\Project;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\ProjectResource\Pages;
use Filament\Forms\Components\ColorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\Split as SplitList;
use Filament\Infolists\Components\Section as SectionList;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\Actions\Action as InfoAction;
use Filament\Infolists\Components\Actions\Action as listAction;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Tables\Actions\ActionGroup;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Project';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make([
                        TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->required(),
                        Select::make('id_produk')
                            ->label('Type Produk')
                            ->options(Produk::all()->pluck('nama_produk', 'id_produk'))
                            ->searchable()
                            ->required(),
                        ColorPicker::make('colour')
                            ->label('Warna')
                            ->required()
                    ])
                        ->grow(false),
                    Section::make([
                        RichEditor::make('brief')
                            ->label('Brief')
                            ->fileAttachmentsDisk('referensi')
                            ->fileAttachmentsDirectory('referensi')
                            ->required(),
                    ])
                        ->grow(true)
                ])
                    ->from('md')
                    ->columnSpan('full')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nama Customer'),
                TextColumn::make('created_at')
                    ->since(),
                TextColumn::make('produk.nama_produk')
                    ->label('Type Produk'),
                TextColumn::make('user.name')
                    ->label('Dikerjakan Oleh')
                    ->getStateUsing(function ($record) {
                        $loggedInUser = auth()->user();

                        // Periksa apakah project telah disetujui oleh user yang login
                        if ($record->user_id === $loggedInUser->id) {
                            return 'Dikerjakan oleh Anda';
                        }

                        // Jika belum disetujui atau oleh orang lain
                        return $record->user?->name ?? 'Belum ada approval';
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'new', // Warna untuk status "new"
                        'success' => 'completed', // Warna untuk status "completed"
                        'warning' => 'pending', // Warna untuk status "pending"
                    ])
                    ->icons([
                        'heroicon-o-bolt' => 'new',       // Ikon untuk status "new"
                        'heroicon-o-check-circle' => 'completed', // Ikon untuk status "completed"
                        'heroicon-o-clock' => 'pending',   // Ikon untuk status "pending"
                    ])
                    ->formatStateUsing(function ($state) {
                        return ucfirst($state); // Mengubah teks menjadi kapital di awal
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                    ->label('Kerjakan')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => auth()->check() && auth()->user()->hasRole('designer') && $record->user_id !== auth()->id())
                    ->action(function ($record) {
                        $user = auth()->user();

                        if ($user) {
                            // Periksa apakah project belum disetujui
                            if (!$record->user_id) {
                                $record->update(['user_id' => $user->id]); // Update dengan user ID yang login
                                $record->update(['status' => 'pending']); // Update dengan user ID yang login

                                // Kirim notifikasi sukses
                                Notification::make()
                                    ->title('Anda telah mengaprove Project')
                                    ->success()
                                    ->send();
                            } else {
                                // Notifikasi jika sudah disetujui
                                $approvedBy = $record->user ? $record->user->name : 'User Tidak Dikenal';
                                Notification::make()
                                    ->title('Project ini telah di aprove oleh ' . $approvedBy)
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            // Notifikasi jika pengguna belum login
                            Notification::make()
                                ->title('You need to be logged in to approve.')
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('uploadResult')
                    ->label(fn($record) => $record->hasMedia('hasil') ? 'Revisi' : 'Upload Design') // Label berdasarkan media
                    ->icon(fn($record) => $record->hasMedia('hasil') ? 'heroicon-o-arrow-path' : 'heroicon-o-arrow-up-tray')
                    ->visible(fn($record) => auth()->check() && $record->user_id === auth()->id())
                    ->form([
                        SpatieMediaLibraryFileUpload::make('hasil')
                            ->label('Hasil Design')
                            ->disk('hasil')
                            ->collection('hasil')
                            ->conversion('thumb')
                            ->conversionsDisk('hasil_thumb')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ]),

                Action::make('markComplete')
                    ->label('Tandai Telah Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => auth()->check() && $record->user_id === auth()->id() && $record->hasMedia('hasil'))
                    ->action(function ($record) {
                        $record->update(['status' => 'completed']); // Tandai project sebagai selesai

                        Notification::make()
                            ->title('Project telah ditandai selesai!')
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    Action::make('addToPesanan')
                        ->label('Tambah ke Pesanan')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->color('success')
                        ->visible(fn($record) => auth()->check() && $record->status === 'completed' && $record->user_id !== auth()->id() && auth()->user()->hasRole('super_admin'))
                        ->action(function ($record) {
                            // Cek apakah project sudah ada di tabel pesanan
                            $existingPesanan = \App\Models\Pesanan::where('project_id', $record->id)->exists();

                            if ($existingPesanan) {
                                Notification::make()
                                    ->title('Project sudah ada di tabel Pesanan.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Buat record baru di tabel pesanan
                            $pesanan = \App\Models\Pesanan::create([
                                'project_id' => $record->id, // Simpan id_project
                            ]);

                            // Pastikan pesanan berhasil disimpan dan ID sudah ada
                            if ($pesanan) {
                                // Setelah disimpan, ambil ID pesanan yang baru saja disimpan
                                $pesanan->save(); // pastikan objek pesanan disimpan terlebih dahulu
                                $lastPesananId = $pesanan->id_pesanan;

                                // Ambil ID divisi berdasarkan nama divisi 'admin'
                                $divisi = \App\Models\Divisi::where('nama_divisi', 'admin')->first(); // Ganti 'admin' jika perlu
                                $divisiId = $divisi ? $divisi->id : null; // Menangani jika divisi tidak ditemukan

                                // Simpan riwayat status ke tabel History setelah pesanan berhasil ditambahkan
                                \App\Models\History::create([
                                    'pesanan_id' => $lastPesananId, // Menggunakan pesanan_id yang baru saja dibuat
                                    'status_awal' => 'new', // Status awal
                                    'status_akhir' => 'new', // Status akhir saat dipindahkan ke produksi
                                    'user_id' => auth()->id(),
                                    'divisi_id' => $divisiId, // ID divisi yang ditentukan
                                ]);

                                Notification::make()
                                    ->title('Project berhasil ditambahkan ke tabel Pesanan.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Terjadi kesalahan saat menambahkan project ke Pesanan.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(), // Tampilkan konfirmasi sebelum action
                    Tables\Actions\ViewAction::make(),
                    Action::make('reset')
                        ->label('Reset')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn($record) => auth()->user()->hasRole('super_admin'))
                        ->action(function ($record) {
                            if ($record->user_id !== null) { // Periksa apakah user_id sudah diset
                                // Set user_id ke null dan status ke 'new'
                                $record->update([
                                    'user_id' => null,
                                    'status' => 'new',
                                    'hasil' => null, // Atau '{}' untuk objek JSON kosong
                                ]);

                                $record->clearMediaCollection('hasil'); // Hapus media hasil

                                // Refresh data untuk memastikan status dan media diperbarui
                                $record->refresh();

                                Notification::make()
                                    ->title('Project telah di-reset.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Project sudah dalam status awal.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                SectionList::make('Detail')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Nama Customer')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('created_at')
                            ->label('dibuat')
                            ->since(),
                        TextEntry::make('produk.nama_produk')
                            ->label('Type Produk'),
                        TextEntry::make('produk.ukuran')
                            ->label('Ukuran'),
                        ColorEntry::make('colour')
                            ->label('Warna')
                            ->copyable()
                            ->copyMessage('Copied!')
                            ->copyMessageDuration(1500)
                    ])
                    ->columnSpan(1),
                SectionList::make('Brief & Hasil')
                    ->schema([
                        TextEntry::make('brief')
                            ->markdown()
                            ->prose(),
                        SectionList::make('Hasil Design')
                            ->collapsed()
                            ->headerActions([
                                ListAction::make('preview')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn($record) => $record->getFirstMediaUrl('hasil')) // Ambil URL media
                                    ->openUrlInNewTab(), // Pastikan URL dibuka di tab baru
                            ])
                            ->schema([
                                ImageEntry::make('')
                                    ->defaultImageUrl(url('/storage/icon/pdf.svg'))
                            ])
                            ->visible(fn($record) => $record->hasMedia('hasil'))
                            ->columns(1)

                    ])
                    ->columnSpan(3),
            ])
            ->columns(4);
    }
}
