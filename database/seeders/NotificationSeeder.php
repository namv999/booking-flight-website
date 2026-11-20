<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(NotificationService::class);
        $admin = User::where('role', 'admin')->first();

        $service->broadcast(
            $admin,
            AppNotification::PROMOTION,
            'Ưu đãi cuối năm: vé nội địa giá tốt',
            'Săn vé nội địa giá tốt cho kỳ nghỉ cuối năm. Xem ngay các chuyến bay đang mở bán.',
            '/#search-box'
        );

        $service->broadcast(
            $admin,
            AppNotification::ANNOUNCEMENT,
            'Chào mừng bạn đến với Jet Charter',
            'Đặt vé, chọn ghế và quản lý hành trình của bạn ngay trên một nền tảng.',
            '/news'
        );
    }
}
