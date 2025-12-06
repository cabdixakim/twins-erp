<?php

namespace App\Services\Depots;

use App\Models\Depot;
use Illuminate\Support\Collection;

class DepotService
{
    public function list(): Collection
    {
        return Depot::orderBy('name')->get();
    }

    public function create(array $data): Depot
    {
        return Depot::create($data);
    }

    public function update(Depot $depot, array $data): Depot
    {
        $depot->update($data);

        return $depot;
    }

    public function delete(Depot $depot): void
    {
        // Later we can prevent delete if depot has transactions
        $depot->delete();
    }
}