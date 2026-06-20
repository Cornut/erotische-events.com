<?php

namespace App\Filament\Resources\Organizers\Schemas;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use Filament\Forms\Components\Placeholder;
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
                Placeholder::make('clicks_total')
                    ->label('Klicks gesamt')
                    ->content(fn (?Organizer $record): int => $record?->clicks()->count() ?? 0)
                    ->columnSpanFull(),
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
                Textarea::make('events_url')
                    ->label('Events-URLs (Auto-Discovery, eine pro Zeile)')
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('scrape_urls')
                    ->label('Scrape-URLs (eine pro Zeile, ohne KI: JSON-LD + iCal)')
                    ->rows(4)
                    ->columnSpanFull(),
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
