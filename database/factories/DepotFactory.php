<?php

namespace Database\Factories;

use App\Models\Depot;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepotFactory extends Factory
{
    protected $model = Depot::class;

    public function definition()
    {
        return [
            'name' => $this->faker->city . ' Depot',
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1,
            'is_active' => true,
        ];
    }
}
