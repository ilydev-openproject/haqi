<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;
use Filament\Forms\Components\Section;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    TextInput::make('name')
                        ->label('nama'),
                    TextInput::make('email')
                        ->email()
                        ->label('Email'),
                    TextInput::make('password')
                        ->password()
                        ->dehydrated(fn($state) => filled($state))
                        ->label('Password'),

                ])
                    ->columns(2)
                    ->columnSpan(3),
                Section::make([
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        // ->multiple()
                        ->preload()
                        ->searchable(),
                    Select::make('divisi_id')
                        ->relationship('divisi', 'nama_divisi')
                        ->preload()
                        ->searchable(),
                ])
                    ->columnSpan(1)
            ])
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama'),
                TextColumn::make('email')
                    ->label('Email'),
                TextColumn::make('is_online') // Kolom status online
                    ->label('Status Online')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return 'Online'; // Tampilkan "Online" jika is_online true
                        } else {
                            // Pastikan last_activity tidak null
                            if ($record->last_activity) {
                                $lastActivity = Carbon::parse($record->last_activity);
                                return "Offline ({$lastActivity->diffForHumans()})"; // Tampilkan dalam format "X waktu yang lalu"
                            } else {
                                return 'Offline (Belum pernah aktif)'; // Tampilkan pesan default jika last_activity null
                            }
                        }
                    })
                    ->color(function ($state) {
                        return $state ? 'success' : 'danger'; // Warna teks: hijau untuk Online, merah untuk Offline
                    }),
                TextColumn::make('divisi.nama_divisi')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('reset_password') // Menambahkan tombol reset password
                    ->label('Reset Password')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Reset Password') // Modal Heading
                    ->Form([
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->minLength(4), // Aturan panjang password
                        TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->required()
                            ->same('password') // Memastikan konfirmasi password sama
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'password' => bcrypt($data['password']), // Update password baru
                        ]);

                        // Menampilkan notifikasi sukses
                        Notification::make()
                            ->title('Password berhasil diperbarui!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
