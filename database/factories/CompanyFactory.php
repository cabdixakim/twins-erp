<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'code' => strtoupper(Str::random(4)),
            'slug' => Str::slug($this->faker->company) . '-' . uniqid(),
            'base_currency' => $this->faker->randomElement(['USD', 'EUR', 'CDF']),
        ];
    }
}
