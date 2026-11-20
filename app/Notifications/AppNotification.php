<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

// Một class dùng chung cho mọi loại thông báo, chỉ lưu qua Laravel database channel.
class AppNotification extends Notification
{
    public const PASSPORT_SUBMITTED = 'passport_submitted';

    public const PASSPORT_APPROVED = 'passport_approved';

    public const PASSPORT_REJECTED = 'passport_rejected';

    public const BOOKING_PAID = 'booking_paid';

    public const ORDER_PAID = 'order_paid';

    public const PROMOTION = 'promotion';

    public const ANNOUNCEMENT = 'announcement';

    public const BROADCAST_TYPES = [
        self::PROMOTION => 'Khuyến mãi',
        self::ANNOUNCEMENT => 'Thông báo chung',
    ];

    private string $kind;

    private string $title;

    private string $message;

    private ?string $url;

    public function __construct(string $kind, string $title, string $message, ?string $url = null)
    {
        $this->kind = $kind;
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->kind,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
