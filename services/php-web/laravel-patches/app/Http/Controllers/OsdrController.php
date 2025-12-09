<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Services\OsdrService;
use App\Http\DTO\OsdrItemDto;

class OsdrController extends Controller
{
    protected OsdrService $osdrService;

    public function __construct(OsdrService $osdrService)
    {
        $this->osdrService = $osdrService;
    }

    public function index(Request $request)
    {
        $limit  = $request->query('limit', '1000');
        $search = $request->query('search', '');
        $sort_by = $request->query('sort_by', 'inserted_at');
        $sort_order = $request->query('sort_order', 'desc');
        $base   = getenv('RUST_BASE') ?: 'http://rust_iss:3000';

        $url = $base . '/osdr/list?limit=' . $limit;
        if ($search !== '') {
            $url .= '&search=' . urlencode($search);
        }

        $json = @file_get_contents($url);
        $data = $json ? json_decode($json, true) : ['items' => []];
        $items = $data['items'] ?? [];

        $items = $this->osdrService->flattenOsdr($items);
        $itemsDto = array_map(fn($item) => new OsdrItemDto($item), $items);
        $itemsArray = array_map(fn($dto) => $dto->toArray(), $itemsDto);

        // Группировка по dataset_id и выбор только последнего по inserted_at
        $grouped = [];
        foreach ($itemsArray as $item) {
            $datasetId = $item['dataset_id'] ?? '';
            $insertedAt = strtotime($item['inserted_at'] ?? '');
            if (!isset($grouped[$datasetId]) || $insertedAt > strtotime($grouped[$datasetId]['inserted_at'] ?? '')) {
                $grouped[$datasetId] = $item;
            }
        }
        $itemsArray = array_values($grouped);

        // Фильтрация по поиску
        if ($search !== '') {
            $itemsArray = array_filter($itemsArray, function($item) use ($search) {
                $title = $item['title'] ?? '';
                $datasetId = $item['dataset_id'] ?? '';
                return stripos($title, $search) !== false || stripos($datasetId, $search) !== false;
            });
        }

        // Сортировка
        usort($itemsArray, function($a, $b) use ($sort_by, $sort_order) {
            $valA = $a[$sort_by] ?? '';
            $valB = $b[$sort_by] ?? '';

            if ($sort_by === 'inserted_at') {
                $valA = strtotime($valA);
                $valB = strtotime($valB);
            }

            if ($sort_order === 'asc') {
                return $valA <=> $valB;
            } else {
                return $valB <=> $valA;
            }
        });

        // Теперь пагинация работает по уже отфильтрованным и отсортированным данным
        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginatedItems = new LengthAwarePaginator(
            array_slice($itemsArray, ($currentPage - 1) * $perPage, $perPage),
            count($itemsArray),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('osdr', [
            'items' => $paginatedItems,
            'src'   => $url,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
        ]);
    }
}
