<?php

namespace App\Modules\Item\Services;

use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemService
{
    /** @param array{status?: string} $filters */
    public function list(QueryParams $params, array $filters): LengthAwarePaginator
    {
        return Item::query()
            ->with('imageMedia')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->applySearch($params, ['name'])
            ->applySort($params, ['name', 'price', 'created_at'], '-created_at')
            ->paginate($params->perPage, page: $params->page);
    }

    /** @param array{name: string, price: float, status?: string} $data */
    public function create(array $data): Item
    {
        return Item::query()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'status' => $data['status'] ?? ItemStatus::ACTIVE->value,
        ])->load('imageMedia');
    }

    /** @param array{name?: string, price?: float, status?: string} $data */
    public function update(Item $item, array $data): Item
    {
        // Changing price here never touches existing bookings — BookingItem
        // snapshots unit_price/total_price at the moment an item is added.
        $item->fill(array_intersect_key($data, array_flip(['name', 'price', 'status'])));

        $item->save();

        return $item->load('imageMedia');
    }

    public function delete(Item $item): void
    {
        $item->delete();
    }
}
