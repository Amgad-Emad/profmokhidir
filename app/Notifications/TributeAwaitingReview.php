<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tribute;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the notification address (or, when none is set, every admin and
 * editor) that a visitor's tribute is waiting for review. The visitor's email
 * is deliberately left out of the message.
 */
class TributeAwaitingReview extends Notification implements ShouldQueue
{
    use Queueable;

    public const int EXCERPT_LENGTH = 200;

    public function __construct(public readonly Tribute $tribute) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tribute = $this->tribute;
        $from = implode(' · ', array_filter([$tribute->relationship?->label(), $tribute->location]));

        return (new MailMessage)
            ->subject(__('A tribute is waiting for review'))
            ->greeting($notifiable instanceof User ? __('Hello :name,', ['name' => $notifiable->name]) : __('Hello,'))
            ->line(__('A new tribute has been submitted on :site and is waiting for review.', ['site' => config('app.name')]))
            ->line($from !== '' ? __('From: :name (:details)', ['name' => $tribute->name, 'details' => $from]) : __('From: :name', ['name' => $tribute->name]))
            ->line('"'.$this->excerpt().'"')
            ->action(__('Open the dashboard'), route('admin.dashboard'))
            ->line(__('It will not appear on the site until it has been approved.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tribute_id' => $this->tribute->getKey(),
            'name' => $this->tribute->name,
        ];
    }

    public function excerpt(): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $this->tribute->message) ?? ''), self::EXCERPT_LENGTH);
    }
}
