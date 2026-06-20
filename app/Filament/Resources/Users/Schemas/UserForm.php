<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('E-Mail')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->label('Rolle')
                    ->options(UserRole::class)
                    ->default('user')
                    ->required()
                    ->live(),
                TextInput::make('password')
                    ->label('Passwort')
                    ->password()
                    ->revealable()
                    // Required on create; on edit, leave blank to keep the current one.
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Beim Bearbeiten leer lassen, um das Passwort beizubehalten.'),
                Select::make('locale')
                    ->label('Sprache')
                    ->options(['de' => 'Deutsch', 'en' => 'English'])
                    ->default('de')
                    ->required(),
                // Links this user as the OWNER of an organizer (organizers.owner_user_id).
                // Virtual field — not a column on users; persisted via the Create/Edit page
                // hooks (see SyncsOrganizerOwnership). Hidden for admins, who act as the
                // parking account for unclaimed (e.g. scraped) organizers.
                Select::make('organizer_id')
                    ->label('Veranstalter:in')
                    ->options(fn (): array => Organizer::orderBy('company_name')->pluck('company_name', 'id')->all())
                    ->searchable()
                    ->placeholder('— keiner —')
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => $get('role') !== UserRole::Admin->value)
                    ->afterStateHydrated(function (Select $component, ?User $record): void {
                        if ($record && $record->role !== UserRole::Admin) {
                            $component->state($record->organizer?->getKey());
                        }
                    })
                    ->helperText('Ordnet diese:n Benutzer:in als Eigentümer:in des gewählten Veranstalters zu. Beim Entfernen geht der Veranstalter zurück an die Admin-Verwaltung.'),
            ]);
    }
}
