<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Inventory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_unit_id',
        'outlet_id',
        'employee_id',
        'process_id',
        'quantity',
        'status',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at',
        'to_outlet_id',
    ];
    protected $casts = [
        'approved_at',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    ];


    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
    public function approver()
{
    return $this->belongsTo(Employee::class, 'approved_by');
}
 public function to_outlet()
{
    return $this->belongsTo(Outlet::class, 'to_outlet_id');
}
 public function productUnit()
{
    return $this->belongsTo(ProductUnits::class, 'product_unit_id');

}
public function approvedBy() // แนะนำให้ใช้ CamelCase ตามมาตรฐาน Laravel
{
    // สมมติว่าคอลัมน์ในตาราง inventories คือ approved_by_id หรือ approved_by
    return $this->belongsTo(\App\Models\User::class, 'approved_by'); 
}


}