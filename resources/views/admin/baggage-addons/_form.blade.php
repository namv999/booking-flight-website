@csrf
@isset($baggageAddon)
    @method('PUT')
@endisset

<div class="mb-3">
    <label class="form-label">Tên gói</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $baggageAddon->name ?? '') }}">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Trọng lượng (kg)</label>
    <input type="number" name="weight_kg" class="form-control @error('weight_kg') is-invalid @enderror"
           value="{{ old('weight_kg', $baggageAddon->weight_kg ?? '') }}">
    @error('weight_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Giá (VNĐ)</label>
    <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror"
           value="{{ old('price', $baggageAddon->price ?? '') }}">
    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<button type="submit" class="btn btn-primary">Lưu</button>
<a href="{{ route('admin.baggage-addons.index') }}" class="btn btn-secondary">Hủy</a>