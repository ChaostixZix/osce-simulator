<?php

namespace App\Notifications;

use App\Models\LearningStreak;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearningStreakAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LearningStreak $streak,
        public string $alertType = 'reminder' // reminder, milestone, broken
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match($this->alertType) {
            'reminder' => $this->reminderMail(),
            'milestone' => $this->milestoneMail(),
            'broken' => $this->brokenStreakMail(),
            default => $this->reminderMail()
        };
    }

    private function reminderMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('🔥 Keep Your Learning Streak Alive!')
            ->greeting('Don\'t break the chain!')
            ->line("Your learning streak is at {$this->streak->current_streak} days.")
            ->line('A quick session today will keep your momentum going.')
            ->action('Continue Learning', route('osce'))
            ->line('Consistency is the key to mastery!');
    }

    private function milestoneMail(): MailMessage
    {
        $nextMilestone = $this->streak->getNextMilestone();

        return (new MailMessage)
            ->subject('🎯 Streak Milestone Approaching!')
            ->greeting('You\'re almost there!')
            ->line("Your learning streak is at {$this->streak->current_streak} days.")
            ->when($nextMilestone, function ($mail) use ($nextMilestone) {
                $mail->line("Just " . ($nextMilestone - $this->streak->current_streak) . " more days to reach your {$nextMilestone}-day milestone!");
            })
            ->action('Keep Going', route('osce'))
            ->line('Great achievements are within reach!');
    }

    private function brokenStreakMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('💪 Ready for a Fresh Start?')
            ->greeting('New beginnings')
            ->line("Your {$this->streak->current_streak}-day streak has ended, but that's okay!")
            ->line('Every expert was once a beginner. Start a new streak today.')
            ->action('Start Fresh', route('osce'))
            ->line('Consistency matters more than perfection!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'learning_streak_alert',
            'alert_type' => $this->alertType,
            'current_streak' => $this->streak->current_streak,
            'longest_streak' => $this->streak->longest_streak,
            'next_milestone' => $this->streak->getNextMilestone(),
            'status' => $this->streak->getStatus(),
            'last_activity_date' => $this->streak->last_activity_date?->toISOString()
        ];
    }
}