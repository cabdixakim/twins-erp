<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition()
    {
        $company = Company::inRandomOrder()->first() ?? Company::factory()->create();
        $supplier = Supplier::inRandomOrder()->where('company_id', $company->id)->first() ?? Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::inRandomOrder()->where('company_id', $company->id)->first();
        return [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'product_id' => $product?->id ?? 1,
            'type' => $this->faker->randomElement(['import', 'local_depot', 'cross_dock']),
            'qty' => $this->faker->randomFloat(2, 1, 1000),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => $company->base_currency,
            'purchase_date' => Carbon::now()->subDays(rand(0, 30)),
            'status' => $this->faker->randomElement(['draft', 'confirmed']),
            'reference' => null,
        ];
    }
}
