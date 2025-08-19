<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportApprovedNotification extends Notification
{
    use Queueable;

    protected $report;
    protected $approver;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report, $approver = null)
    {
        $this->report = $report;
        $this->approver = $approver;
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
            'message' => 'Laporan untuk debitur "' . $this->report->borrower->name . '" telah disetujui.',
            'action_url' => route('reports.show', $this->report->id),
            'action_text' => 'Lihat Laporan',
            'approver' => $this->approver ? $this->approver->name : null
        ];
    }
}