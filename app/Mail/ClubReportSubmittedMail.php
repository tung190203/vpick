<?php

namespace App\Mail;

use App\Models\Club\ClubReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubReportSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClubReport $report
    ) {
    }

    public function envelope(): Envelope
    {
        $clubName = $this->report->club?->name ?? 'CLB';
        return new Envelope(
            subject: "Báo cáo CLB mới - {$clubName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.club-report-submitted',
        );
    }
}
