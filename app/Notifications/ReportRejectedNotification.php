<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportRejectedNotification extends Notification
{
    use Queueable;

    protected $report;
    protected $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report, string $rejectionReason)
    {
        $this->report = $report;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Hanya database, tanpa mail
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'borrower_name' => $this->report->borrower->name,
            'rejection_reason' => $this->rejectionReason,
            'message' => 'Laporan untuk debitur "' . $this->report->borrower->name . '" telah ditolak.',
            'action_url' => route('reports.edit', $this->report->id),
            'action_text' => 'Edit Laporan'
        ];
    }
}