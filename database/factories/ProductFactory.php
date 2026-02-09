<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' ' . $this->faker->unique()->numerify('###'),
            'company_id' => null, // Set in seeder
            'is_active' => true,
        ];
    }
}
