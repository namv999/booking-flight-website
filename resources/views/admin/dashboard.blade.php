@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Bảng điều khiển Thống kê</h2>

    <!-- Thẻ thông tin nhanh tỷ lệ ghế -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tổng số ghế</h5>
                    <h3>{{ number_format($totalSeats) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Ghế đã đặt (Booked)</h5>
                    <h3>{{ number_format($bookedSeats) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tỷ lệ lấp đầy</h5>
                    <h3>{{ $occupancyRate }}%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Khu vực Biểu đồ -->
    <div class="row">
        <!-- Biểu đồ Doanh thu theo tháng -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">Doanh thu theo tháng (Thành công)</div>
                <div class="card-body">
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Biểu đồ Tỷ lệ lấp đầy ghế -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">Tỷ lệ lấp đầy ghế</div>
                <div class="card-body d-flex justify-content-center">
                    <div style="width: 250px;">
                        <canvas id="occupancyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Biểu đồ Số chuyến bay theo hãng -->
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header">Số lượng chuyến bay theo Hãng hàng không</div>
                <div class="card-body">
                    <canvas id="airlineFlightChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nhúng Chart.js từ CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Biểu đồ doanh thu theo tháng
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: {!! json_encode($monthlyRevenue) !!},
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 2. Biểu đồ tỷ lệ lấp đầy ghế (Donut Chart)
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

    // 3. Biểu đồ số chuyến bay theo hãng
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
        options: {
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>
@endsection
