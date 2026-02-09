<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
