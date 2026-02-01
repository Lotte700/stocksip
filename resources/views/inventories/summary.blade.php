@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('inventories.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
    <label class="form-label fw-bold">Search Product Name</label>
    <select name="product_id" class="form-control select2">
    <option></option> @foreach($summary as $product)
        <option value="{{ $product['product_id'] }}">{{ $product['product_name'] }} - {{ $product['product_description'] }}</option>
    @endforeach
</select>
</div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('inventories.index') }}" class="btn btn-light text-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i> Monthly Summary</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Product / Category</th>
                        <th class="text-center">Opening</th>
                        <th class="text-center">Transfer</th>
                        <th class="text-center">Sell</th>
                        <th class="text-center">Request</th>
                        <th class="text-center">Spoil</th>
                        <th class="text-center table-success">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $row)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('inventories.show', $row['product_id']) }}" class="product-link">
                                {{ $row['product_name'] }}
                            </a><br>
                            <small class="text-muted">{{ $row['category_name'] }}</small>
                            <small class="text-muted">{{ $row['product_description'] }}</small>

                        </td>
                        <td class="text-center bg-light">{!! format_inventory_balance($row['opening'], $row['base_ratio']) !!}</td>
                        <td class="text-center">{!! format_process_units($row['processes']['transfer'] ?? []) !!}</td>
                        <td class="text-center">{!! format_process_units($row['processes']['sell'] ?? []) !!}</td>
                        <td class="text-center">{!! format_process_units($row['processes']['open'] ?? []) !!}</td>
                        <td class="text-center">{!! format_process_units($row['processes']['spoil'] ?? []) !!}</td>
                        <td class="text-center table-success fw-bold">{!! format_inventory_balance($row['total'], $row['base_ratio']) !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
