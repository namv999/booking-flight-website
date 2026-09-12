{{-- TEMP LAYOUT: dùng layouts/app.blade.php placeholder, sẽ thay khi C giao layout thật --}}
@extends('layouts.app')

@section('title', 'Nhập thông tin hành khách')

@section('content')
    <h3>Nhập thông tin hành khách</h3>

    <p>
        Chuyến: {{ $flight->departureAirport->iata_code }} → {{ $flight->arrivalAirport->iata_code }}<br>
        Giờ bay: {{ $flight->departure_time }} - {{ $flight->arrival_time }}<br>
        Hãng: {{ $flight->aircraft->airline->name }}
    </p>

    @if ($errors->any())
        <div>
            <strong>Vui lòng kiểm tra lại các trường sau:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('booking.passengers.store') }}">
        @csrf

        {{-- ADULTS --}}
        <h4>Người lớn ({{ $pending['adults'] }})</h4>
        @for ($i = 0; $i < $pending['adults']; $i++)
            <fieldset>
                <legend>Người lớn #{{ $i + 1 }}</legend>

                <label>Họ tên *</label>
                <input type="text" name="adults[{{ $i }}][full_name]" value="{{ old("adults.$i.full_name") }}" required>

                <label>Số giấy tờ (CCCD/Hộ chiếu)</label>
                <input type="text" name="adults[{{ $i }}][document_number]" value="{{ old("adults.$i.document_number") }}">

                <label>Ngày sinh</label>
                <input type="date" name="adults[{{ $i }}][date_of_birth]" value="{{ old("adults.$i.date_of_birth") }}">
            </fieldset>
        @endfor

        {{-- CHILDREN --}}
        @if ($pending['children'] > 0)
            <h4>Trẻ em ({{ $pending['children'] }})</h4>
            @for ($i = 0; $i < $pending['children']; $i++)
                <fieldset>
                    <legend>Trẻ em #{{ $i + 1 }}</legend>

                    <label>Họ tên *</label>
                    <input type="text" name="children[{{ $i }}][full_name]" value="{{ old("children.$i.full_name") }}" required>

                    <label>Số giấy tờ</label>
                    <input type="text" name="children[{{ $i }}][document_number]" value="{{ old("children.$i.document_number") }}">

                    <label>Ngày sinh</label>
                    <input type="date" name="children[{{ $i }}][date_of_birth]" value="{{ old("children.$i.date_of_birth") }}">
                </fieldset>
            @endfor
        @endif

        {{-- INFANTS --}}
        @if ($pending['infants'] > 0)
            <h4>Em bé ({{ $pending['infants'] }})</h4>
            @for ($i = 0; $i < $pending['infants']; $i++)
                <fieldset>
                    <legend>Em bé #{{ $i + 1 }}</legend>

                    <label>Họ tên *</label>
                    <input type="text" name="infants[{{ $i }}][full_name]" value="{{ old("infants.$i.full_name") }}" required>

                    <label>Số giấy tờ</label>
                    <input type="text" name="infants[{{ $i }}][document_number]" value="{{ old("infants.$i.document_number") }}">

                    <label>Ngày sinh</label>
                    <input type="date" name="infants[{{ $i }}][date_of_birth]" value="{{ old("infants.$i.date_of_birth") }}">

                    <label>Đi kèm người lớn *</label>
                    <select name="infants[{{ $i }}][companion_adult_index]" required>
                        <option value="">-- Chọn người lớn --</option>
                        @for ($a = 0; $a < $pending['adults']; $a++)
                            <option value="{{ $a }}" {{ old("infants.$i.companion_adult_index") == $a ? 'selected' : '' }}>
                                Người lớn #{{ $a + 1 }}
                            </option>
                        @endfor
                    </select>
                </fieldset>
            @endfor
        @endif

        <button type="submit">Xác nhận đặt vé</button>
    </form>
@endsection

@section('scripts')
@endsection