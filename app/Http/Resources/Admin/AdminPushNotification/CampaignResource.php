<?php

namespace App\Http\Resources\Admin\AdminPushNotification;

use App\Services\Admin\AdminPushNotification\CampaignRecipientResolverFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resolverData = CampaignRecipientResolverFactory::makeWithConfig($this->resource);
        /** @var \App\Services\Admin\AdminPushNotification\PushNotificationRecipientResolver $resolver */
        $resolver = $resolverData['resolver'];
        $config = $resolverData['config'];

        // Tính success_rate
        $successRate = null;
        if ($this->actual_recipient_count > 0 && $this->actual_recipient_count !== null) {
            $successRate = round(($this->success_count / $this->actual_recipient_count) * 100, 1);
        }

        // Lấy danh sách failed users
        $failedUsers = [];
        if ($this->relationLoaded('results')) {
            $failedResults = $this->results->where('status', 'failed')->take(50);
            $failedUsers = $failedResults->map(fn($r) => [
                'id' => $r->user_id,
                'name' => $r->user?->full_name,
            ])->values()->toArray();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'image_url' => $this->image_url,
            'action_type' => $this->action_type?->value,
            'action_id' => $this->action_id,
            'recipient_type' => $this->recipient_type?->value,
            'recipient_label' => $resolver->label($config),
            'send_type' => $this->send_type?->value,
            'scheduled_at' => $this->scheduled_at?->toIsoString(),
            'sent_at' => $this->sent_at?->toIsoString(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'estimated_recipient_count' => $this->estimated_recipient_count,
            'actual_recipient_count' => $this->actual_recipient_count,
            'success_count' => $this->success_count,
            'failure_count' => $this->failure_count,
            'success_rate' => $successRate,
            'failed_users' => $failedUsers,
            'created_by' => $this->created_by,
            'creator_name' => $this->whenLoaded('creator', fn() => $this->creator?->full_name),
            'created_at' => $this->created_at?->toIsoString(),
        ];
    }
}