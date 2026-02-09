<?php

namespace Database\Factories;

use App\Models\Transporter;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransporterFactory extends Factory
{
    protected $model = Transporter::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
