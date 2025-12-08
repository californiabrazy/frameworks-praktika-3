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

        // Теперь пагинация работает по уже отфильтрованным данным
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
        ]);
    }
}
