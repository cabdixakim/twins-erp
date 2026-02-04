<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $products = Product::query()
            ->where('company_id', $cid)
            ->orderBy('name')
            ->paginate(30);

        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'code'     => 'nullable|string|max:32',
            'base_uom' => 'nullable|string|max:16',
        ]);

        Product::create([
            'company_id' => $cid,
            'name'       => trim($data['name']),
            'code'       => $data['code'] ? strtoupper(trim($data['code'])) : null,
            'base_uom'   => $data['base_uom'] ? strtoupper(trim($data['base_uom'])) : 'L',
            'is_active'  => true,
            'created_by' => $u?->id,
            'updated_by' => $u?->id,
        ]);

        return back()->with('status', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $u = auth()->user();

        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'code'      => 'nullable|string|max:32',
            'base_uom'  => 'nullable|string|max:16',
            'is_active' => 'nullable|boolean',
        ]);

        $product->update([
            'name'       => trim($data['name']),
            'code'       => $data['code'] ? strtoupper(trim($data['code'])) : null,
            'base_uom'   => $data['base_uom'] ? strtoupper(trim($data['base_uom'])) : $product->base_uom,
            'is_active'  => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $product->is_active,
            'updated_by' => $u?->id,
        ]);

        return back()->with('status', 'Product updated.');
    }

    public function toggleActive(Product $product)
    {
        $u = auth()->user();

        $product->update([
            'is_active'  => !$product->is_active,
            'updated_by' => $u?->id,
        ]);

        return back()->with('status', 'Product status updated.');
    }
}