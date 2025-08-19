<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportSubmittedNotification extends Notification
{
    use Queueable;

    protected $report;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
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
            'message' => 'Laporan untuk debitur "' . $this->report->borrower->name . '" telah disubmit untuk approval.',
            'action_url' => route('reports.show', $this->report->id),
            'action_text' => 'Lihat Laporan'
        ];
    }
}