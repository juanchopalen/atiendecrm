<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'policies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('policies.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('policy_number')
            ->columns([
                TextColumn::make('policy_number')
                    ->label(__('policies.fields.policy_number'))
                    ->searchable(),
                TextColumn::make('line_of_business')
                    ->label(__('policies.fields.line_of_business'))
                    ->formatStateUsing(fn (string $state) => __("policies.lines_of_business.{$state}")),
                TextColumn::make('insurer')
                    ->label(__('policies.fields.insurer')),
                TextColumn::make('expiration_date')
                    ->label(__('policies.fields.expiration_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('policies.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("policies.statuses.{$state}")),
                TextColumn::make('payment_frequency')
                    ->label(__('policies.fields.payment_frequency'))
                    ->formatStateUsing(fn (string $state) => __("policies.payment_frequencies.{$state}"))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('filament-actions::create.single.label', ['label' => __('policies.label')]))
                    ->modalHeading(__('filament-actions::create.single.modal.heading', ['label' => __('policies.label')])),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
