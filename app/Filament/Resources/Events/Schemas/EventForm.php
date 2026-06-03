<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventAccommodation;
use App\Enums\EventStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organizer_id')
                    ->relationship('organizer', 'company_name')
                    ->searchable()
                    ->required(),
                Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->searchable(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('short_description'),
                Textarea::make('long_description')
                    ->columnSpanFull(),
                FileUpload::make('main_image')
                    ->image(),
                DateTimePicker::make('start_date')
                    ->required(),
                DateTimePicker::make('end_date'),
                Select::make('status')
                    ->options(EventStatus::class)
                    ->default('draft')
                    ->required(),
                Select::make('audience')
                    ->multiple()
                    ->options([
                        'singles' => 'Singles',
                        'couples' => 'Couples',
                        'men' => 'Men',
                        'women' => 'Women',
                        'lgbtq' => 'LGBTQ+',
                        'everyone' => 'Everyone',
                    ])
                    ->columnSpanFull(),
                TextInput::make('min_participants')
                    ->numeric(),
                TextInput::make('max_participants')
                    ->numeric(),
                Select::make('languages')
                    ->multiple()
                    ->options([
                        'de' => 'Deutsch',
                        'en' => 'English',
                        'es' => 'Español',
                        'fr' => 'Français',
                        'other' => 'Other',
                    ])
                    ->columnSpanFull(),
                Select::make('accommodation')
                    ->options(EventAccommodation::class)
                    ->default('none')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('EUR'),
                TextInput::make('booking_url')
                    ->url()
                    ->required(),
                TextInput::make('source_url')
                    ->url(),
                Select::make('categories')
                    ->relationship('categories', 'name_de')
                    ->multiple()
                    ->preload(),
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
                Select::make('teachers')
                    ->relationship('teachers', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }
}
