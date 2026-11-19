<?php

namespace App\Console\Commands;

use App\Services\FlightScheduleGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateFlights extends Command
{
    protected $signature = 'flights:generate {--from=2026-11-23} {--to=2026-12-08} {--occupancy=0.35}';

    protected $description = 'Sinh chuyến bay và ghế mẫu theo khoảng ngày (chạy lại không tạo trùng)';

    public function handle(FlightScheduleGenerator $generator): int
    {
        try {
            $from = Carbon::parse($this->option('from'))->startOfDay();
            $to = Carbon::parse($this->option('to'))->startOfDay();
        } catch (\Throwable) {
            $this->error('Ngày không hợp lệ. Dùng định dạng YYYY-MM-DD.');

            return self::FAILURE;
        }

        $occupancy = (float) $this->option('occupancy');

        if ($to->lt($from) || $from->diffInDays($to) > 90 || $occupancy < 0 || $occupancy > 1) {
            $this->error('Khoảng ngày không hợp lệ (to >= from, tối đa 90 ngày) hoặc occupancy ngoài khoảng 0–1.');

            return self::FAILURE;
        }

        $result = $generator->generate($from, $to, $occupancy);
        $this->info("Đã tạo {$result['flights']} chuyến ({$result['international']} quốc tế), {$result['seats']} ghế. Bỏ qua {$result['skipped']} chuyến đã có.");

        if ($result['missing']) {
            $this->warn('Thiếu sân bay/hãng/máy bay cho: '.implode(', ', $result['missing']).'. Hãy chạy Airport/Airline/Aircraft/Seat seeder trước.');
        }

        return self::SUCCESS;
    }
}
