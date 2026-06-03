<?php

namespace App\Filament\Resources\Organizers\Schemas;

use App\Enums\OrganizerVerificationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('owner_user_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('contact_name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('website')
                    ->url(),
                TagsInput::make('social_links')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('logo'),
                TextInput::make('slug')
                    ->required(),
                Select::make('verification_status')
                    ->options(OrganizerVerificationStatus::class)
                    ->default('pending')
                    ->required(),
            ]);
    }
}
