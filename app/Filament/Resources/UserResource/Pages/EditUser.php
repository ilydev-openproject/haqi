<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use App\Models\Divisi;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\UserResource;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Actions\Action;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            // Actions\Action::make('add_divisi')
            //     ->label('Add Divisi') // Label tombol
            //     ->icon('heroicon-o-plus-circle') // Ikon tombol
            //     ->modalHeading('Tambah Divisi') // Judul modal
            //     ->modalDescription('Masukkan informasi divisi yang ingin ditambahkan.')
            //     ->modalSubmitActionLabel('Simpan')
            //     ->modalWidth('sm')
            //     ->form([ // Definisi form dalam modal
            //         TextInput::make('nama_divisi')
            //             ->label('Nama Divisi')
            //             ->placeholder('Masukkan nama divisi')
            //             ->required(),
            //     ])
            //     ->action(function (array $data) {
            //         // Simpan data divisi ke database
            //         Divisi::create([
            //             'nama_divisi' => $data['nama_divisi'],
            //         ]);

            //         Notification::make()
            //             ->title('Divisi berhasil ditambahkan.')
            //             ->success()
            //             ->send();
            //     }),
        ];
    }
}
