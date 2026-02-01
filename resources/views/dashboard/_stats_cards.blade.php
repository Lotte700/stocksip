<div class="row mb-4">
    {{-- Pending Approvals --}}
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-primary text-uppercase mb-1">Pending Approvals</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($pendingApprovalsCount) }} Items</div>
                    </div>
                    <div class="ms-3 text-gray-300">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Revenue --}}
    <div class="col-xl-3 col-md-6 mb-4">
        <a href="{{ route('report.sales') }}" class="text-decoration-none h-100">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body text-dark">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-bold text-success text-uppercase mb-1">Today's Revenue</div>
                            <div class="h5 mb-0 fw-bold">฿{{ number_format($todayRevenue, 2) }}</div>
                        </div>
                        <div class="ms-3 text-gray-300">
                            <i class="bi bi-currency-dollar fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Inventory Value --}}
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-info text-uppercase mb-1">Inventory Value</div>
                        <div class="h5 mb-0 fw-bold text-dark">฿{{ number_format($totalInventoryValue, 2) }}</div>
                    </div>
                    <div class="ms-3 text-gray-300">
                        <i class="bi bi-box-seam fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    <div class="col-xl-3 col-md-6 mb-4">
    <a href="{{ route('inventories.index', ['low_stock_ids' => $lowStockIds]) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-warning text-uppercase mb-1">Low Stock Alert</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ $lowStockCount }} Products</div>
                        <small class="text-muted">Below 10 units & ฿5,000</small>
                    </div>
                    <div class="ms-3 text-gray-300">
                        <i class="bi bi-exclamation-triangle fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </a>
</div>
</div>