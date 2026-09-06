{{-- TEMP LAYOUT --}}

@if (session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif

@if (session('status'))
    <div class="alert-status">{{ session('status') }}</div>
@endif

<div class="container py-4">
    <h3 class="mb-3">Tìm chuyến bay</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('flights.search.results') }}" method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Điểm đi</label>
            <select name="departure_airport_id" class="form-select" required>
                <option value="">-- Chọn --</option>
                @foreach ($airports as $airport)
                    <option value="{{ $airport->id }}" {{ old('departure_airport_id') == $airport->id ? 'selected' : '' }}>
                        {{ $airport->city }} ({{ $airport->iata_code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Điểm đến</label>
            <select name="arrival_airport_id" class="form-select" required>
                <option value="">-- Chọn --</option>
                @foreach ($airports as $airport)
                    <option value="{{ $airport->id }}" {{ old('arrival_airport_id') == $airport->id ? 'selected' : '' }}>
                        {{ $airport->city }} ({{ $airport->iata_code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Ngày khởi hành</label>
            <input type="date" name="departure_date" class="form-control"

                   value="{{ old('departure_date') }}" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Hạng vé</label>
            <select name="fare_class_id" class="form-select" required>
                @foreach ($fareClasses as $index => $fareClass)
                    <option value="{{ $fareClass->id }}" {{ $loop->first ? 'selected' : '' }}>
                        {{ $fareClass->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Người lớn</label>
            <input type="number" name="adults" class="form-control" min="1" max="9" value="{{ old('adults', 1) }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Trẻ em</label>
            <input type="number" name="children" class="form-control" min="0" max="9" value="{{ old('children', 0) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Em bé</label>
            <input type="number" name="infants" class="form-control" min="0" max="9" value="{{ old('infants', 0) }}">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Tìm chuyến bay</button>
        </div>
    </form>
</div>