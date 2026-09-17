{{-- resources/views/admin/dashboard.blade.php --}}
{{-- Dashboard ADMIN (route 'admin.dashboard') - đã có DashboardController thật --}}

@extends('layouts.admin')

@section('content')
<section class="bf-admin-heading">
    <div>
        <span class="bf-eyebrow">Tổng quan vận hành</span>
        <h1>Bảng điều khiển Thống kê</h1>
    </div>
</section>

<section class="bf-admin-stat-grid" aria-label="Chỉ số vận hành">
    <article>
        <span>Tổng số ghế</span>
        <strong>{{ number_format($totalSeats) }}</strong>
    </article>
    <article>
        <span>Ghế đã đặt (Booked)</span>
        <strong>{{ number_format($bookedSeats) }}</strong>
    </article>
    <article>
        <span>Tỷ lệ lấp đầy</span>
        <strong>{{ $occupancyRate }}%</strong>
    </article>
</section>

<section class="bf-admin-grid">
    <div class="bf-admin-panel bf-admin-panel--wide">
        <div class="bf-panel-heading">
            <div><span class="bf-eyebrow">Theo tháng</span><h2>Doanh thu (Thành công)</h2></div>
        </div>
        <canvas id="revenueChart" height="120"></canvas>
    </div>

    <div class="bf-admin-panel">
        <div class="bf-panel-heading">
            <div><span class="bf-eyebrow">Tổng quan ghế</span><h2>Tỷ lệ lấp đầy</h2></div>
        </div>
        <div style="max-width: 250px; margin: 0 auto;">
            <canvas id="occupancyChart"></canvas>
        </div>
    </div>

    <div class="bf-admin-panel bf-admin-panel--full">
        <div class="bf-panel-heading">
            <div><span class="bf-eyebrow">Theo hãng</span><h2>Số lượng chuyến bay</h2></div>
        </div>
        <canvas id="airlineFlightChart" height="80"></canvas>
    </div>
</section>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'],
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: {!! json_encode($monthlyRevenue) !!},
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Đã đặt (%)', 'Trống (%)'],
            datasets: [{
                data: [{{ $occupancyRate }}+0, {{ $emptyRate }}+0],
                backgroundColor: ['#28a745', '#e4e5e7']
            }]
        }
    });

    const airlineCtx = document.getElementById('airlineFlightChart').getContext('2d');
    new Chart(airlineCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($airlineNames) !!},
            datasets: [{
                label: 'Số lượng chuyến bay',
                data: {!! json_encode($airlineFlightCounts) !!},
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
</script>
@endsection