<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FocusListController extends Controller
{
    public function toggle(Request $request)
    {
        // ตรวจสอบว่ามี product_id ส่งมาหรือไม่
        $productId = $request->product_id;
        $outletId = Auth::user()->employee->outlet_id;

        if (!$productId) {
            return response()->json(['message' => 'Product ID is required'], 400);
        }

        // ตรวจสอบสถานะปัจจุบันในตาราง focus_lists
        $focus = DB::table('focus_lists')
            ->where('outlet_id', $outletId)
            ->where('product_id', $productId)
            ->first();

        if ($focus) {
            // ถ้ามีอยู่แล้ว ให้ลบออก (Unfavorite)
            DB::table('focus_lists')->where('id', $focus->id)->delete();
            return response()->json(['status' => 'removed']);
        } else {
            // ถ้ายังไม่มี ให้เพิ่มเข้าไป (Favorite)
            DB::table('focus_lists')->insert([
                'outlet_id' => $outletId,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['status' => 'added']);
        }
    }
}