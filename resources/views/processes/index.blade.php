@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="bi bi-clock-history me-2"></i>Pending Approvals</h2>
        <a href="{{ route('processes.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> New Transaction
        </a>
    </div>

    {{-- 🟢 Section 1: Daily Open Records (Accordion) --}}
    <div class="mb-5">
        <h4 class="mb-3 text-success fw-bold">
            <i class="bi bi-box-arrow-in-down me-2"></i>Daily Open Records
        </h4>
        
        <div class="accordion border shadow-sm" id="openAccordion">
            @forelse($openProcesses as $date => $items)
                @php 
                    // สร้าง ID เฉพาะตัวสำหรับแต่ละวันเพื่อป้องกันจอกระพริบ
                    $groupId = 'group_' . \Carbon\Carbon::parse($date)->format('Ymd'); 
                @endphp
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#{{ $groupId }}" 
                                aria-expanded="false">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                <span>
                                    <i class="bi bi-calendar3 me-2 text-success"></i>
                                    Date: <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
                                </span>
                                <span class="badge bg-success rounded-pill px-3">{{ $items->count() }} items</span>
                            </div>
                        </button>
                    </h2>
                    
                    {{-- ID ของ div ต้องตรงกับ data-bs-target ของปุ่มด้านบน --}}
                    <div id="{{ $groupId }}" class="accordion-collapse collapse" data-bs-parent="#openAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light small">
                                        <tr>
                                            <th class="ps-4">Product</th>
                                            <th>Staff</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $process)
                                            <tr>
                                                <td class="ps-4">
                                                    <strong>{{ $process->productUnit->product->name }}</strong><br>
                                                    <small class="text-muted"> {{ $process->productUnit->product->description }} <strong>{{ $process->productUnit->name }}</strong></small>
                                                    
                                                </td>
                                                <td>{{ $process->employee->name }}</td>
                                                <td class="text-center fw-bold text-primary">{{ $process->quantity }}</td>
                                                <td class="text-end pe-4">
                                                    @include('processes.partials.actions', ['process' => $process])
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-5 text-center bg-white">
                    <i class="bi bi-check-circle text-muted fs-1"></i>
                    <p class="mt-2 text-muted">No pending "Open" requests.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- 🔵 Section 2: Transfers & Other Movements (Standard Table) --}}
    <div>
        <h4 class="mb-3 text-primary fw-bold">
            <i class="bi bi-arrow-left-right me-2"></i>Transfers & Others
        </h4>
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Outlet Request</th>
                            <th>Staff</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($otherProcesses as $process)
                            <tr>
                                <td class="ps-4 small text-muted">{{ \Carbon\Carbon::parse($process->created_at)->format('H:i') }}</td>
                                <td><strong>{{ $process->productUnit->product->name }}</strong>
                                <small class="text-muted"> {{ $process->productUnit->product->description }} <strong>{{ $process->productUnit->name }}</strong></small>
                                </td>
                                <td class="small">
    @if(strtolower($process->process->name) === 'transfer')
        {{-- ถ้าเป็นรายการโอน --}}
        <div class="d-flex align-items-center">
            <span class="badge bg-light text-primary border">
                {{ $process->fromOutlet->name ?? 'Unknown Source' }}
            </span>
            <i class="bi bi-arrow-right mx-2 text-muted"></i>
            <span class="badge bg-light text-success border">
                {{ $process->outlet->name }}
            </span>
        </div>
    @else
        {{-- รายการปกติ --}}
        {{ $process->outlet->name }}
    @endif
</td>
                                
                                <td class="small">{{ $process->outlet->name }}</td>
                                <td>{{ $process->employee->name }}</td>
                                <td class="text-center fw-bold">{{ $process->quantity }}</td>
                                <td class="text-end pe-4">
                                    @include('processes.partials.actions', ['process' => $process])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">No other pending movements.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection