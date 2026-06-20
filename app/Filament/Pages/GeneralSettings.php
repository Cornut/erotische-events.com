<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class GeneralSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Allgemein';

    protected static ?string $title = 'Allgemein';

    protected string $view = 'filament.pages.general-settings';

    public bool $loginRequired = false;

    public function mount(): void
    {
        $this->loginRequired = Setting::flag('login_required');
    }

    public function save(): void
    {
        Setting::put('login_required', $this->loginRequired ? '1' : '0');

        Notification::make()
            ->title('Einstellungen gespeichert')
            ->success()
            ->send();
    }
}
