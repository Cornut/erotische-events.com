<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\EventPublishingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organizer.id')
                    ->searchable(),
                TextColumn::make('venue.name')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('short_description')
                    ->searchable(),
                ImageColumn::make('main_image'),
                TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('min_participants')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_participants')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('accommodation')
                    ->badge()
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('booking_url')
                    ->searchable(),
                TextColumn::make('source_url')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record) => $record->status === EventStatus::PendingReview)
                    ->action(fn (Event $record) => app(EventPublishingService::class)->publish($record)),
                Action::make('reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record) => $record->status === EventStatus::PendingReview)
                    ->action(fn (Event $record) => app(EventPublishingService::class)->reject($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
