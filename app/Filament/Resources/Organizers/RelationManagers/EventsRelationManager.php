<?php

namespace App\Filament\Resources\Organizers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Events';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_date')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('favorited_by_count')
                    ->label('Favoriten')
                    ->counts('favoritedBy')
                    ->sortable(),
                TextColumn::make('clicks_count')
                    ->label('Klicks')
                    ->counts('clicks')
                    ->sortable(),
            ])
            ->defaultSort('start_date', 'asc');
    }
}
