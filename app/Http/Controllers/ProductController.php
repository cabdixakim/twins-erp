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
            'name'            => 'required|string|max:120',
            'code'            => 'nullable|string|max:32',
            'base_uom'        => 'nullable|string|max:16',
            'allowed_loss_pct'=> 'nullable|numeric|min:0|max:100',
            'default_density' => 'nullable|numeric|min:0|max:2',
        ]);

        // Check for duplicate product (company_id + name) — case-insensitive
        $exists = \App\Models\Product::query()
            ->where('company_id', $cid)
            ->whereRaw('LOWER(name) = LOWER(?)', [$data['name']])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'A product with this name already exists for your company.'])->withInput();
        }

        Product::create([
            'company_id'      => $cid,
            'name'            => trim($data['name']),
            'code'            => $data['code'] ? strtoupper(trim($data['code'])) : null,
            'base_uom'        => $data['base_uom'] ? strtoupper(trim($data['base_uom'])) : 'L',
            'allowed_loss_pct'=> isset($data['allowed_loss_pct']) ? (float) $data['allowed_loss_pct'] : null,
            'default_density' => isset($data['default_density']) ? (float) $data['default_density'] : null,
            'is_active'       => true,
            'created_by'      => $u?->id,
            'updated_by'      => $u?->id,
        ]);

        return back()->with('status', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $u = auth()->user();

        $data = $request->validate([
            'name'            => 'required|string|max:120',
            'code'            => 'nullable|string|max:32',
            'base_uom'        => 'nullable|string|max:16',
            'is_active'       => 'nullable|boolean',
            'allowed_loss_pct'=> 'nullable|numeric|min:0|max:100',
            'default_density' => 'nullable|numeric|min:0|max:2',
        ]);

        $product->update([
            'name'            => trim($data['name']),
            'code'            => $data['code'] ? strtoupper(trim($data['code'])) : null,
            'base_uom'        => $data['base_uom'] ? strtoupper(trim($data['base_uom'])) : $product->base_uom,
            'is_active'       => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $product->is_active,
            'allowed_loss_pct'=> isset($data['allowed_loss_pct']) ? (float) $data['allowed_loss_pct'] : $product->allowed_loss_pct,
            'default_density' => isset($data['default_density']) ? (float) $data['default_density'] : $product->default_density,
            'updated_by'      => $u?->id,
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