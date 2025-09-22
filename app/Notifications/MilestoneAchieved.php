<?php

namespace App\Notifications;

use App\Models\GrowthMilestone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MilestoneAchieved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GrowthMilestone $milestone
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🏆 Milestone Achieved!')
            ->greeting('Congratulations!')
            ->line("You've achieved a new milestone: {$this->milestone->milestone_title}")
            ->line($this->milestone->milestone_description)
            ->line("Badge: {$this->milestone->getBadgeIcon()}")
            ->action('View Your Progress', route('growth.dashboard'))
            ->line('Keep up the great work!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'milestone_achieved',
            'milestone_id' => $this->milestone->id,
            'milestone_title' => $this->milestone->milestone_title,
            'milestone_description' => $this->milestone->milestone_description,
            'badge_icon' => $this->milestone->getBadgeIcon(),
            'badge_color' => $this->milestone->getBadgeColor(),
            'rarity' => $this->milestone->getRarity(),
            'achieved_at' => $this->milestone->achieved_at->toISOString()
        ];
    }
}