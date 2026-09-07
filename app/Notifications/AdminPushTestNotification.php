<?php

namespace App\Notifications;

class AdminPushTestNotification extends BaseNotification
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $imageUrl = null,
        public ?string $actionType = null,
        public ?int $actionId = null,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $extra = [
            'type' => 'ADMIN_PUSH_TEST',
            'admin_id' => (string) $notifiable->id,
        ];

        if ($this->actionType && $this->actionType !== 'NONE' && $this->actionId) {
            $extra['action_type'] = $this->actionType;
            $extra['action_id'] = (string) $this->actionId;
            $extra['action_url'] = match ($this->actionType) {
                'TOURNAMENT' => "tournament-detail/{$this->actionId}",
                'MINI_TOURNAMENT' => "mini-tournament-detail/{$this->actionId}",
                'CLUB' => "club-detail/{$this->actionId}",
                default => null,
            };
        }

        if ($this->imageUrl) {
            $extra['image_url'] = $this->imageUrl;
        }

        return self::payload($this->title, $this->content, $extra);
    }
}
