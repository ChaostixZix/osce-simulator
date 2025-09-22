<?php

namespace App\Notifications;

use App\Models\RefresherCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefresherCaseReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RefresherCase $refresherCase
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contentType = match($this->refresherCase->content_type) {
            'quick_quiz' => 'Quick Quiz',
            'case_review' => 'Case Review',
            'skill_drill' => 'Skill Drill',
            default => 'Practice Session'
        };

        return (new MailMessage)
            ->subject('📚 Time for a Refresher!')
            ->greeting('Ready to practice?')
            ->line("A new {$contentType} is ready for you to review.")
            ->line("Based on: {$this->refresherCase->osceCase?->title}")
            ->line("Difficulty: " . ucfirst($this->refresherCase->difficulty))
            ->action('Start Refresher', route('growth.refresher.show', $this->refresherCase->id))
            ->line('Consistent practice leads to mastery!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refresher_case_ready',
            'refresher_case_id' => $this->refresherCase->id,
            'content_type' => $this->refresherCase->content_type,
            'osce_case_title' => $this->refresherCase->osceCase?->title,
            'difficulty' => $this->refresherCase->difficulty,
            'estimated_time' => $this->refresherCase->getFormattedContent()['estimated_time'] ?? '10 minutes',
            'next_reminder_date' => $this->refresherCase->next_reminder_date->toISOString()
        ];
    }
}