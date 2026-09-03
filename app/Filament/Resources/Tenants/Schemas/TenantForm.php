<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('tenants.fields.name'))
                    ->required(),
                TextInput::make('slug')
                    ->label(__('tenants.fields.slug'))
                    ->required(),
                TextInput::make('tax_id')
                    ->label(__('tenants.fields.tax_id')),
                Toggle::make('is_active')
                    ->label(__('tenants.fields.is_active'))
                    ->required(),
                Section::make(__('tenants.sections.notifications'))
                    ->components([
                        TextInput::make('features.notifications.days_to_pay')
                            ->label(__('tenants.fields.days_to_pay'))
                            ->helperText(__('tenants.helpers.days_to_pay'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90),
                    ]),
            ]);
    }
}
