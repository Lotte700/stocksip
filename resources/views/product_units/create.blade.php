@extends('layouts.app')


@section('title', 'Add Product')


@section('content')
<form action="{{ route('product_units.store') }}" method="POST">
@csrf

<div class="mb-2">
<label>Product ID</label>
<select name="product_id" class="form-control">
@foreach($products as $product)
<option value="{{ $product->id }}">{{ $product->name }} -- {{ $product->description }}</option>
@endforeach
</select>
</div>


<div class="mb-2">
<label>Name</label>
<input type="text" name="name" class="form-control">
</div>

<div class="mb-2">
<label>quality</label>
<input type="text" name="ml" class="form-control">
</div>

<div class="mb-2">
<label>price</label>
<input type="text" name="price" class="form-control">
</div>

<button class="btn btn-success">Save</button>
</form>
@endsection