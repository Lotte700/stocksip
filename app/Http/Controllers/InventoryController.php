<?php

namespace App\Http\Controllers;

use App\Models\FocusList;
use Illuminate\Support\Facades\Auth;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Category;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $onlyFocus = $request->has('only_focus');
        $outletId = Auth::user()->employee->outlet_id;
        $focusIds = FocusList::where('outlet_id', $outletId)
        ->pluck('product_id')
        ->toArray();
        $productId = $request->get('product_id');

        $lowStockIds = $request->get('low_stock_ids');
        
        // 1. รับค่าจาก Filter
        $month = $request->get('month', now()->format('Y-m'));
        $categoryId = $request->get('category_id');
        
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $categories = Category::orderBy('category_name')->get();

        // 2. ดึงข้อมูล Inventory พร้อม Eager Loading ให้ครบชั้น
       $inventories = Inventory::with([
        'productUnit.product.category', 
        'productUnit.product.productUnits', 
        'process'
    ])
    ->where('status', 'approved')
    ->where('outlet_id', $outletId)
    ->whereHas('productUnit.product', function ($query) use ($categoryId, $lowStockIds, $onlyFocus, $focusIds) {
        
        // 1. กรองตามหมวดหมู่
        $query->when($categoryId, function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });

        // 2. กรองสินค้าที่ติด Low Stock Alert จาก Dashboard
        $query->when($lowStockIds, function ($q) use ($lowStockIds) {
            $q->whereIn('id', (array)$lowStockIds);
        });

        // 3. กรองเฉพาะรายการที่กด Favorite (Focus List)
        if ($onlyFocus) {
            $query->whereIn('id', $focusIds);
        }
    })
    // 4. กรองตามชื่อสินค้าที่เลือกจาก Search/Select2
    ->when($productId, function ($query) use ($productId) {
        $query->whereHas('productUnit', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        });
    })
    ->get();

        // 3. จัดกลุ่มและคำนวณ Summary
        $summary = $inventories
            ->groupBy(fn ($i) => $i->productUnit->product->id)
            ->map(function ($rows) use ($start, $end) {
                $product = $rows->first()->productUnit->product;
                
                // คัดแยกหน่วยใหญ่ (ml มากสุด) และหน่วยเล็ก (ml น้อยสุด)
                $allUnits = $product->productUnits->sortByDesc('ml');
                $bigUnit = $allUnits->first();
                $smallUnit = $allUnits->last();
                
                // คำนวณ Ratio (เช่น 750 / 150 = 5)
                $ratio = ($smallUnit && $smallUnit->ml > 0) ? ($bigUnit->ml / $smallUnit->ml) : 1;

                // ฟังก์ชันช่วยแปลงหน่วยต่างๆ ให้เป็นค่า ml รวม
                $getMlSum = function ($collection) use ($allUnits) {
                    $mlSum = 0;
                    foreach ($collection as $item) {
                        $mlSum += ($item->quantity * ($item->productUnit->ml ?? 0));
                    }
                    return $mlSum;
                };

                // ฟังก์ชันช่วยแปลง ml กลับเป็น Array ของหน่วยใหญ่/เล็ก (ปัดเศษ)
                $convertMlToUnits = function ($totalMl) use ($bigUnit, $smallUnit, $ratio) {
                    if ($totalMl == 0) return [];
                    
                    // ป้องกันหารด้วยศูนย์
                    $bigMl = $bigUnit->ml ?: 1;
                    $smallMl = $smallUnit->ml ?: 1;

                    $finalBig = (int)($totalMl / $bigMl);
                    $remainingMl = $totalMl % $bigMl;
                    $finalSmall = round($remainingMl / $smallMl);

                    // กรณีพิเศษ: ถ้าหน่วยย่อยปัดขึ้นจนครบ 1 หน่วยใหญ่
                    if (abs($finalSmall) >= $ratio) {
                        $finalBig += ($finalSmall > 0 ? 1 : -1);
                        $finalSmall = 0;
                    }

                    $res = [];
                    if ($finalBig != 0) $res[$bigUnit->name] = $finalBig;
                    if ($finalSmall != 0) $res[$smallUnit->name] = $finalSmall;
                    
                    return $res;
                };

                // คำนวณ Opening (ยอดก่อนเริ่มเดือน)
                $openingMl = $getMlSum($rows->where('created_at', '<', $start));
                
                // คำนวณ Total Balance (ยอดทั้งหมดจนถึงสิ้นเดือน)
                $totalMl = $getMlSum($rows->where('created_at', '<=', $end));

                return [
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'product_description' => $product->description,
                    'category_name' => $product->category->category_name ?? 'Uncategorized',
                    'base_ratio'    => $ratio,
                    'opening'       => $convertMlToUnits($openingMl),
                    'processes'     => $rows->whereBetween('created_at', [$start, $end])
                                        ->groupBy(fn($r) => $r->process->name)
                                        ->map(fn($p) => $p->groupBy(fn($r) => $r->productUnit->name)
                                        ->map(fn($u) => $u->sum('quantity'))),
                    'total'         => $convertMlToUnits($totalMl),
                ];
            })->values();

        return view('inventories.summary', compact('summary', 'month', 'categories', 'categoryId', 'focusIds', 'lowStockIds', 'onlyFocus'));
    }

    /**
     * ===============================
     * Inventory Report (รายเดือน / ต่อ product)
     * ===============================
     */
 public function show(Request $request, $productId)
{
    $outletId = Auth::user()->employee->outlet_id;
    $month = $request->get('month', now()->format('Y-m'));
    $start = Carbon::parse($month)->startOfMonth();
    $end   = Carbon::parse($month)->endOfMonth();

    // 1. ดึงข้อมูล (ปรับเงื่อนไขให้ดึงทั้งขาเข้าและขาออก)
    $allData = Inventory::with([
            'productUnit.product.productUnits',
            'approvedBy.employee', 
            'process',
            'fromOutlet', // 👈 เพิ่ม
            'outlet'      // 👈 เพิ่ม
        ])
        ->where('status', 'approved')
        ->where(function($q) use ($outletId) {
            $q->where('outlet_id', $outletId)      // เราเป็นปลายทาง (รับเข้า)
              ->orWhere('from_outlet_id', $outletId); // เราเป็นต้นทาง (ส่งออก)
        })
        ->whereHas('productUnit', fn ($q) => $q->where('product_id', $productId))
        ->get();

    // 2. ข้อมูลหน่วยและ Ratio
    $product = \App\Models\Product::with('productUnits')->find($productId);
    $allUnits = $product->productUnits->sortByDesc('ml');
    $bigUnitName = $allUnits->first()->name;
    $smallUnitName = $allUnits->last()->name;
    $baseRatio = ($allUnits->last()->ml > 0) ? ($allUnits->first()->ml / $allUnits->last()->ml) : 1;

    // ฟังก์ชันคำนวณยอด Balance (ต้องเช็คว่าถ้าเราเป็นคนส่ง ยอดต้องติดลบ)
    $calculateRawBalance = function($items) use ($outletId) {
        return $items->groupBy(fn ($r) => $r->productUnit->name)
            ->map(function ($unitRows) use ($outletId) {
                return $unitRows->sum(function($row) use ($outletId) {
                    // ถ้าเราเป็น from_outlet (คนส่ง) ให้ยอดติดลบ
                    return ($row->from_outlet_id == $outletId) ? -abs($row->quantity) : abs($row->quantity);
                });
            });
    };

    $normalizeUnits = function($data) use ($bigUnitName, $smallUnitName) {
        return [
            $bigUnitName => $data[$bigUnitName] ?? 0,
            $smallUnitName => $data[$smallUnitName] ?? 0
        ];
    };

    $openingBalance = $normalizeUnits($calculateRawBalance($allData->where('created_at', '<', $start)));
    $closingBalance = $normalizeUnits($calculateRawBalance($allData->where('created_at', '<=', $end)));

    // จัดกลุ่มรายวัน
    $inMonth = $allData->whereBetween('created_at', [$start, $end])
        ->sortBy('created_at')
        ->groupBy(fn ($r) => substr($r->created_at, 0, 10));

    return view('inventories.show', compact('inMonth', 'openingBalance', 'closingBalance', 'month', 'baseRatio', 'bigUnitName', 'smallUnitName'));
}
}