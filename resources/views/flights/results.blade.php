{{-- TEMP LAYOUT --}}
@if (session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif

@if (session('status'))
    <div class="alert-status">{{ session('status') }}</div>
@endif

<div class="container py-4">
    <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm mb-3">&laquo; Tìm lại</a>
    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    @if (session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    <h4 class="mb-1">{{ $departureAirport->city }} ({{ $departureAirport->iata_code }}) &rarr; {{ $arrivalAirport->city }} ({{ $arrivalAirport->iata_code }})</h4>
    <p class="text-muted mb-4">
        {{ \Carbon\Carbon::parse($departureDate)->format('d/m/Y') }} &middot; Hạng {{ $fareClass->name }}
    </p>

    @forelse ($flights as $flight)
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">
                        {{ $flight->aircraft->airline->name }}
                        @if ($flight->status === 'delayed')
                            <span class="badge bg-warning text-dark ms-2">Delayed</span>
                        @endif
                    </div>
                    <div>{{ $flight->aircraft->model }}</div>
                    <div>
                        {{ \Carbon\Carbon::parse($flight->departure_time)->format('H:i') }}
                        &rarr;
                        {{ \Carbon\Carbon::parse($flight->arrival_time)->format('H:i') }}
                    </div>
                    <div class="text-muted small">Còn {{ $flight->available_seats }} ghế</div>
                </div>
                <div class="text-end">
                    <div class="fs-5 fw-bold text-danger">
                        {{ number_format($flight->min_price, 0, ',', '.') }} VND
                    </div>
                    {{-- NOTE(Namv): route seat-selection chưa build, tạm href="#" --}}
                    <form action="{{ route('booking.hold') }}" method="POST">
                        @csrf
                        <input type="hidden" name="flight_id" value="{{ $flight->id }}">
                        <input type="hidden" name="fare_class_id" value="{{ $fareClass->id }}">
                        <input type="hidden" name="adults" value="{{ $adults }}">
                        <input type="hidden" name="children" value="{{ $children }}">
                        <input type="hidden" name="infants" value="{{ $infants }}">
                        <button type="submit" class="btn btn-primary">Chọn</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Không tìm thấy chuyến bay phù hợp.</div>
    @endforelse

    {{ $flights->links() }}
</div>
