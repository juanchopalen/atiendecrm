<?php

namespace App\Filament\Resources\Policies\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label(__('policies.fields.client_id'))
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('policy_number')
                    ->label(__('policies.fields.policy_number'))
                    ->required(),
                Select::make('line_of_business')
                    ->label(__('policies.fields.line_of_business'))
                    ->options(fn () => __('policies.lines_of_business'))
                    ->required(),
                TextInput::make('insurer')
                    ->label(__('policies.fields.insurer'))
                    ->required(),
                DatePicker::make('start_date')
                    ->label(__('policies.fields.start_date'))
                    ->required(),
                DatePicker::make('expiration_date')
                    ->label(__('policies.fields.expiration_date'))
                    ->required(),
                Select::make('status')
                    ->label(__('policies.fields.status'))
                    ->options(fn () => __('policies.statuses'))
                    ->default('active')
                    ->required(),
                TextInput::make('premium')
                    ->label(__('policies.fields.premium'))
                    ->numeric(),
                Select::make('payment_frequency')
                    ->label(__('policies.fields.payment_frequency'))
                    ->options(fn () => __('policies.payment_frequencies'))
                    ->default('anual')
                    ->required(),
                SpatieMediaLibraryFileUpload::make('attachments')
                    ->label(__('policies.fields.attachments'))
                    ->collection('attachments')
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }
}
