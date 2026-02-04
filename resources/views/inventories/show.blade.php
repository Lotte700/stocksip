@extends('layouts.app')

@section('title', 'Inventory Detail')

@section('content')
<div class="container">
    <h2 class="mb-4">Inventory Movement Detail</h2>

    {{-- 🔹 ส่วนเลือกเดือน --}}
    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 fw-bold">Select Month:</label>
            </div>
            <div class="col-auto">
                <input type="month" name="month" value="{{ $month }}" class="form-control">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary px-4">Filter</button>
            </div>
            <div class="col text-end">
                 <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary">Back to List</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 15%">Date</th>
                        <th>Details (Unit)</th>
                        <th class="text-end">Open</th>
                        <th class="text-end">Sell</th>
                        <th class="text-end">Transfer</th>
                        <th class="text-end">Spoil</th>
                        <th class="text-end bg-secondary text-white">Running Balance</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- 🔹 Opening Balance Row --}}
                    <tr class="table-warning fw-bold">
                        <td colspan="6">Opening Balance</td>
                        <td class="text-end">
                            {!! format_inventory_balance($openingBalance, $baseRatio) !!}
                        </td>
                    </tr>

                    @php
                        $runningMap = is_array($openingBalance) ? $openingBalance : $openingBalance->toArray(); 
                    @endphp

                    @foreach($inMonth as $date => $rows)
                        @php
                            // 1. รวมยอดดิบรายวันแยกตามหน่วย
                            $dailyData = $rows->groupBy(fn($r) => $r->productUnit->name)
                                              ->map(fn($unitRows) => $unitRows->sum('quantity'));
                            
                            // 2. อัปเดตยอดสะสมใน Running Map
                            foreach($dailyData as $unit => $qty) {
                                $runningMap[$unit] = ($runningMap[$unit] ?? 0) + $qty;
                            }

                            // 3. จัดลำดับหน่วยใหม่ (ขวดต้องอยู่ก่อนแก้วเสมอ)
                            $sortedRunning = [
                                $bigUnitName => $runningMap[$bigUnitName] ?? 0,
                                $smallUnitName => $runningMap[$smallUnitName] ?? 0
                            ];
                        @endphp
                        <tr>
                            <td>{{ Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                            <td>
                                @foreach($dailyData as $uName => $uQty)
                                    @if($uQty != 0)
                                        <span class="badge bg-light text-dark border">{{ $uName }}</span>
                                    @endif
                                @endforeach
                            </td>

                            {{-- 🟢 ช่อง OPEN --}}
                            <td class="text-end text-primary">
                                @php
                                    $openItems = $rows->filter(fn($r) => $r->process && $r->process->name === 'open');
                                    $openApprover = $openItems->whereNotNull('approved_by')->first()?->approvedBy?->employee;
                                @endphp
                                {!! format_process_units($openItems->groupBy(fn($r) => $r->productUnit->name)->map(fn($u) => $u->sum('quantity'))) !!}
                                <br>
                                <small style="font-size: 0.7rem;">
                                    @if($openApprover)
                                        <i class="bi bi-unlock-fill text-primary"></i> {{ $openApprover->name }}
                                        @if($openApprover->outlet_id == Auth::user()->employee->outlet_id)
                                            <span class="badge bg-light text-dark" style="font-size: 0.6rem;">Local</span>
                                        @else
                                            <span class="badge bg-light text-danger" style="font-size: 0.6rem;">HQ/Other</span>
                                        @endif
                                    @elseif($openItems->isNotEmpty())
                                        <span class="text-muted" style="font-style: italic;">Wait...</span>
                                    @endif
                                </small>
                            </td>

                            {{-- 🔴 ช่อง SELL --}}
                            <td class="text-end text-danger">
                                {!! format_process_units($rows->where('process.name','sell')->groupBy(fn($r)=>$r->productUnit->name)->map(fn($u)=>$u->sum('quantity'))) !!}
                            </td>

                            {{-- 🔵 ช่อง TRANSFER --}}
                            <td class="text-end text-info">
                                @php
                                    $transferItems = $rows->filter(fn($r) => $r->process && $r->process->name === 'transfer');
                                    $transApprover = $transferItems->whereNotNull('approved_by')->first()?->approvedBy?->employee;
                                @endphp
                                {!! format_process_units($transferItems->groupBy(fn($r) => $r->productUnit->name)->map(fn($u) => $u->sum('quantity'))) !!}
                                <br>
                                <small style="font-size: 0.7rem;">
                                    @if($transApprover)
                                        <i class="bi bi-person-check text-info"></i> {{ $transApprover->name }}
                                        @if($transApprover->outlet_id == Auth::user()->employee->outlet_id)
                                            <span class="text-muted">(Local)</span>
                                        @else
                                            <span class="text-danger">(HQ)</span>
                                        @endif
                                    @elseif($transferItems->isNotEmpty())
                                        <span class="text-danger">Pending</span>
                                    @endif
                                </small>
                            </td>

                            {{-- 🟠 ช่อง SPOIL --}}
                            <td class="text-end text-warning">
                                {!! format_process_units($rows->where('process.name','spoil')->groupBy(fn($r)=>$r->productUnit->name)->map(fn($u)=>$u->sum('quantity'))) !!}
                            </td>

                            {{-- ⚡ ยอดสะสม Running Balance --}}
                            <td class="text-end fw-bold bg-light">
                                {!! format_inventory_balance($sortedRunning, $baseRatio) !!}
                            </td>
                        </tr>
                    @endforeach

                    {{-- 🔹 Closing Balance Row --}}
                    <tr class="table-success fw-bold">
                        <td colspan="6">Closing Balance</td>
                        <td class="text-end">
                            {!! format_inventory_balance($closingBalance, $baseRatio) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection