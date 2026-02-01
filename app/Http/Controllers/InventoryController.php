<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Category;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        
        $outletId = Auth::user()->employee->outlet_id;

        $productId = $request->get('product_id');
        
        // 1. รับค่าจาก Filter
        $month = $request->get('month', now()->format('Y-m'));
        $categoryId = $request->get('category_id');
        
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $categories = Category::orderBy('category_name')->get();

        // 2. ดึงข้อมูล Inventory พร้อม Eager Loading ให้ครบชั้น
        $inventories = Inventory::with([
                'productUnit.product.category', 
                'productUnit.product.productUnits', // ดึงหน่วยพี่น้องทั้งหมดมาคำนวณ Ratio
                'process'
            ])
            ->where('status', 'approved')
            ->where('outlet_id', $outletId)
            ->whereHas('productUnit.product', function ($query) use ($categoryId) {
                $query->when($categoryId, function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            })->when($productId, function ($query) use ($productId) { // 👈 กรองสินค้าถ้ามีการค้นหา
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

        return view('inventories.summary', compact('summary', 'month', 'categories', 'categoryId'));
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

    // 1. ดึงข้อมูลพร้อมโหลดความสัมพันธ์
    $allData = Inventory::with(['productUnit.product.productUnits', 'process'])
        ->where('status', 'approved')
        ->where('outlet_id', $outletId)
        ->whereHas('productUnit', fn ($q) => $q->where('product_id', $productId))
        ->get();

    // 2. หาข้อมูลหน่วยเพื่อใช้กำหนดลำดับ (สำคัญมาก)
    $product = \App\Models\Product::with('productUnits')->find($productId);
    $allUnits = $product->productUnits->sortByDesc('ml'); // [Bottle, Glass]
    $bigUnitName = $allUnits->first()->name;
    $smallUnitName = $allUnits->last()->name;
    $baseRatio = ($allUnits->last()->ml > 0) ? ($allUnits->first()->ml / $allUnits->last()->ml) : 1;

    // ฟังก์ชันช่วยจัดเรียง Array ให้หน่วยใหญ่ขึ้นก่อนเสมอ
    $normalizeUnits = function($data) use ($bigUnitName, $smallUnitName) {
        return [
            $bigUnitName => $data[$bigUnitName] ?? 0,
            $smallUnitName => $data[$smallUnitName] ?? 0
        ];
    };

    /**
     * 3. Opening Balance (จัดเรียงหน่วยใหม่)
     */
    $rawOpening = $allData->where('created_at', '<', $start)
        ->groupBy(fn ($r) => $r->productUnit->name)
        ->map(fn ($unitRows) => $unitRows->sum('quantity'));
    
    $openingBalance = $normalizeUnits($rawOpening);

    /**
     * 4. In Month (จัดกลุ่มตามวัน)
     */
    $inMonth = $allData->whereBetween('created_at', [$start, $end])
        ->sortBy('created_at')
        ->groupBy(fn ($r) => substr($r->created_at, 0, 10));

    /**
     * 5. Closing Balance (จัดเรียงหน่วยใหม่)
     */
    $rawClosing = $allData->where('created_at', '<=', $end)
        ->groupBy(fn ($r) => $r->productUnit->name)
        ->map(fn ($unitRows) => $unitRows->sum('quantity'));

    $closingBalance = $normalizeUnits($rawClosing);

    return view('inventories.show', compact(
        'inMonth',
        'openingBalance',
        'closingBalance',
        'month',
        'baseRatio',
        'bigUnitName',
        'smallUnitName' // ส่งชื่อหน่วยไปช่วยจัดลำดับใน Blade ด้วย
    ));
}
}