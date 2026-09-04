<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Exceptions\WhatsAppApiException;
use App\Models\Ticket;
use App\Services\WhatsApp\WhatsAppReplyService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('interactions.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('channel')
                    ->label(__('interactions.fields.channel'))
                    ->options(fn () => __('interactions.channels'))
                    ->required(),
                Textarea::make('message')
                    ->label(__('interactions.fields.message'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                TextColumn::make('channel')
                    ->label(__('interactions.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("interactions.channels.{$state}")),
                TextColumn::make('user.name')
                    ->label(__('interactions.fields.user_id')),
                TextColumn::make('message')
                    ->label(__('interactions.fields.message'))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('interactions.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('interactions.empty_state'))
            ->emptyStateDescription(__('interactions.empty_state_description'))
            ->headerActions([
                $this->replyByWhatsAppAction(),
                CreateAction::make()
                    ->label(__('filament-actions::create.single.label', ['label' => __('interactions.label')]))
                    ->modalHeading(__('filament-actions::create.single.modal.heading', ['label' => __('interactions.label')]))
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] ??= auth()->id();

                        return $data;
                    }),
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

    protected function replyByWhatsAppAction(): Action
    {
        return Action::make('replyByWhatsapp')
            ->label(__('interactions.reply_by_whatsapp.label'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn () => filled($this->getOwnerTicket()->client?->phone))
            ->modalDescription(fn () => $this->sessionWindowStatusMessage())
            ->schema([
                Textarea::make('mensaje')
                    ->label(__('interactions.reply_by_whatsapp.message_field'))
                    ->required()
                    ->rows(4)
                    ->autofocus(),
            ])
            ->action(function (array $data): void {
                $ticket = $this->getOwnerTicket();
                $replyService = app(WhatsAppReplyService::class);

                if (! $replyService->isSessionWindowOpen($ticket)) {
                    Notification::make()
                        ->title(__('interactions.reply_by_whatsapp.window_closed_title'))
                        ->body(__('interactions.reply_by_whatsapp.window_closed_body'))
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    $replyService->reply($ticket, $data['mensaje'], auth()->id());

                    Notification::make()
                        ->title(__('interactions.reply_by_whatsapp.sent_title'))
                        ->success()
                        ->send();
                } catch (WhatsAppApiException $e) {
                    Notification::make()
                        ->title(__('interactions.reply_by_whatsapp.failed_title'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getOwnerTicket(): Ticket
    {
        /** @var Ticket $ticket */
        $ticket = $this->getOwnerRecord();

        return $ticket;
    }

    protected function sessionWindowStatusMessage(): string
    {
        $replyService = app(WhatsAppReplyService::class);
        $ticket = $this->getOwnerTicket();
        $lastMessageAt = $replyService->lastCustomerMessageAt($ticket);

        if (! $lastMessageAt) {
            return __('interactions.reply_by_whatsapp.window_never_written');
        }

        if ($replyService->isSessionWindowOpen($ticket)) {
            $expiresAt = $lastMessageAt->copy()->addHours(WhatsAppReplyService::SESSION_WINDOW_HOURS);

            return __('interactions.reply_by_whatsapp.window_open', ['time' => $expiresAt->format('d/m H:i')]);
        }

        return __('interactions.reply_by_whatsapp.window_closed_body');
    }
}
