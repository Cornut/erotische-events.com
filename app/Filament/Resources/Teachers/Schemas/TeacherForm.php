<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('photo'),
                TagsInput::make('links')
                    ->columnSpanFull(),
            ]);
    }
}
