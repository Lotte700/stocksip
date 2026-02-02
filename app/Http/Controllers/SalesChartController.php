<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Support\Facades\DB;
use App\Models\ProductUnit;
use App\Models\ProductUnits;
use Illuminate\Support\Facades\Auth;

class SalesChartController extends Controller{


public function salesReport(Request $request)
{
    $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
    $endDate = $request->get('end_date', now()->format('Y-m-d'));
    $categoryId = $request->get('category_id');
    // รับค่าจากทั้งการคลิกที่กราฟ หรือการพิมพ์ค้นหาในช่อง Search
    $selectedProduct = $request->get('selected_product') ?: $request->get('search_product');

    $categories = \App\Models\Category::all();

    // 1. กราฟบน: ยอดขายรวมแยกตามสินค้า
    $productSalesQuery = Inventory::select([
            'products.name as product_name',
            DB::raw('SUM(ABS(inventories.quantity)) as total_qty'),
            DB::raw('SUM(ABS(inventories.quantity) * product_units.price) as total_price')
        ])
        ->join('product_units', 'inventories.product_unit_id', '=', 'product_units.id')
        ->join('products', 'product_units.product_id', '=', 'products.id')
        ->where('inventories.outlet_id', '=', Auth::user()->employee->outlet_id)
        ->where('inventories.process_id', 1)
        ->where('inventories.status', 'approved')
        ->whereBetween('inventories.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

    if ($categoryId) {
        $productSalesQuery->where('products.category_id', $categoryId);
    }

    $productSales = $productSalesQuery->groupBy('products.name')->get();
    
    // สร้าง Options เพื่อให้กราฟ 1 สามารถคลิกได้ (Event Click)
    $chart1 = (new LarapexChart)->lineChart()
        ->setTitle('ยอดขายรวมแยกตามสินค้า')
        ->addData('จำนวนชิ้น', $productSales->pluck('total_qty')->toArray())
        ->addData('ยอดเงิน (K)', $productSales->map(fn($item) => round($item->total_price / 1000, 2))->toArray())
        ->setXAxis($productSales->pluck('product_name')->toArray())
        ->setHeight(300);

    // 2. กราฟล่าง: แสดงแนวโน้มรายวัน (เมื่อค้นหา หรือ คลิกเลือกสินค้า)
    $chart2 = null;
    if ($selectedProduct) {
        $dailyProductSales = Inventory::select([
                DB::raw('DATE(inventories.created_at) as date'),
                DB::raw('SUM(ABS(inventories.quantity)) as qty'),
                DB::raw('SUM(ABS(inventories.quantity) * product_units.price) as daily_total_price')
            ])
            ->join('product_units', 'inventories.product_unit_id', '=', 'product_units.id')
            ->join('products', 'product_units.product_id', '=', 'products.id')
            ->where('inventories.outlet_id', '=', Auth::user()->employee->outlet_id)
            ->where('products.name', 'LIKE', "%{$selectedProduct}%") // ค้นหาแบบกึ่งตรง
            ->where('inventories.process_id', 1)
            ->where('inventories.status', 'approved')
            ->whereBetween('inventories.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        if ($dailyProductSales->count() > 0) {
            $chart2 = (new LarapexChart)->barChart()
                ->setTitle("แนวโน้มยอดขายรายวัน: $selectedProduct")
                ->addData('จำนวนชิ้น', $dailyProductSales->pluck('qty')->toArray())
                ->addData('ยอดเงิน (K)', $dailyProductSales->map(fn($item) => round($item->daily_total_price / 1000, 2))->toArray())
                ->setXAxis($dailyProductSales->pluck('date')->toArray())
                ->setHeight(300)
                ->setColors(['#008FFB', '#FEB019']);
        }
    }

    $salesData = Inventory::with(['productUnit.product', 'employee'])
        ->where('process_id', 1)
        ->where('inventories.outlet_id', '=', Auth::user()->employee->outlet_id)
        ->where('status', 'approved')
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->orderBy('created_at', 'desc')
        ->get();

    return view('reports.sales', compact('chart1', 'chart2', 'salesData', 'startDate', 'endDate', 'categories', 'categoryId', 'selectedProduct'));
}

public function index()
{
    $outletId = Auth::user()->employee->outlet_id;

    // 1. Pending Approvals (จำนวนรายการรออนุมัติ)
    $pendingApprovalsCount = Inventory::where('process_id', '!=', 1)
        ->where('process_id', '!=', 4)
        ->where('status', 'pending')
        ->where('outlet_id', $outletId)
        ->count();

    // 2. Today's Revenue (รายได้วันนี้)
    $todayRevenue = Inventory::where('inventories.status', 'approved')
        ->whereRaw('DATE(inventories.created_at) = ?', [\Carbon\Carbon::now('Asia/Bangkok')->format('Y-m-d')])
        ->where('inventories.process_id', '=', 1)
        ->where('inventories.outlet_id', $outletId)
        ->join('product_units', 'inventories.product_unit_id', '=', 'product_units.id')
        ->sum(DB::raw('ABS(inventories.quantity) * product_units.price'));

    // 3. คำนวณยอดคงเหลือราย Product Unit เพื่อหา Total Value และ Low Stock
    // แก้ไข Group By ให้ครบตามกฎ Strict Mode ของ MySQL
    $inventoryBalances = Inventory::select([
            'product_units.product_id',       // สำคัญ: ต้องมีเพื่อใช้ pluck ID ไปทำ Filter
            'inventories.product_unit_id',
            'product_units.price',
            'products.name as product_name',
            'product_units.name as unit_name',
            DB::raw('SUM(inventories.quantity) as current_balance')
        ])
        ->join('product_units', 'inventories.product_unit_id', '=', 'product_units.id')
        ->join('products', 'product_units.product_id', '=', 'products.id')
        ->where('inventories.status', 'approved')
        ->where('inventories.outlet_id', $outletId)
        ->groupBy(
            'product_units.product_id', 
            'inventories.product_unit_id', 
            'product_units.price', 
            'products.name', 
            'product_units.name'
        )
        ->get();

    // 4. มูลค่าคลังสินค้าทั้งหมด (Sum of balance * price)
    $totalInventoryValue = $inventoryBalances->sum(function($item) {
        return $item->current_balance * $item->price;
    });

    // 5. สินค้าที่สต็อกต่ำ (Filter เงื่อนไข: จำนวน < 10 และ มูลค่ารวม < 2000)
    $lowStockItems = $inventoryBalances->filter(function($item) {
        $isLowQuantity = $item->current_balance > 0 && $item->current_balance < 10;
        $isLowValue = ($item->current_balance * $item->price) < 2000; 
        return $isLowQuantity && $isLowValue;
    });

    // จำนวนรายการสินค้าที่เตือน (นับจำนวนชุด)
    $lowStockCount = $lowStockItems->count();

    // ดึงเฉพาะ Product IDs เก็บเข้า Array เพื่อส่งไปใช้ในหน้า Report
    $lowStockIds = $lowStockItems->pluck('product_id')->unique()->toArray();

    // 6. Recent Transactions (5 รายการล่าสุดที่รออนุมัติ)
    $recentTransactions = Inventory::with(['productUnit.product'])
        ->where('inventories.status', 'pending')
        ->where('inventories.outlet_id', $outletId)
        ->where('inventories.process_id', '!=', 3) // ไม่เอาการตรวจนับ
        ->orderBy('inventories.created_at', 'desc')
        ->take(5)
        ->get();

    return view('dashboard.index', compact(
        'pendingApprovalsCount', 
        'todayRevenue', 
        'totalInventoryValue', 
        'lowStockCount',    // ใช้ตัวแปรนี้แสดงใน Card
        'lowStockIds',      // ใช้ตัวแปรนี้สร้าง Link ใน View
        'recentTransactions'
    ));
}
}