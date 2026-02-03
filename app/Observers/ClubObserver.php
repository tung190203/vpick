<?php

namespace App\Observers;

use App\Models\Club\Club;

class ClubObserver
{
    /**
     * Handle the Club "created" event.
     */
    public function created(Club $club): void
    {
        //
    }

    /**
     * Handle the Club "updated" event.
     */
    public function updated(Club $club): void
    {
        //
    }

    /**
     * Handle the Club "deleting" event.
     * Đổi tên và set status inactive khi soft delete để tránh conflict.
     */
    public function deleting(Club $club): void
    {
        if (!$club->isForceDeleting()) {
            $originalName = $club->getOriginal('name');
            $timestamp = time();

            $club->name = $originalName . '_deleted_' . $timestamp;

            $club->status = 'inactive';

            $club->saveQuietly();
        }
    }

    /**
     * Handle the Club "deleted" event.
     */
    public function deleted(Club $club): void
    {
        //
    }

    /**
     * Handle the Club "restored" event.
     * Khi restore, set status = draft để user xem xét trước khi active lại.
     */
    public function restored(Club $club): void
    {
        // Set status = draft để user phải manually active lại
        // Tránh trường hợp club tự động active mà chưa kiểm tra
        $club->status = 'draft';
        $club->saveQuietly();
    }

    /**
     * Handle the Club "force deleted" event.
     */
    public function forceDeleted(Club $club): void
    {
        //
    }
}
