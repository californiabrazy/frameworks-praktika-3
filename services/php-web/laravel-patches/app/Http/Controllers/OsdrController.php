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
        $limit = $request->query('limit', '1000');
        $base  = getenv('RUST_BASE') ?: 'http://rust_iss:3000';

        $json  = @file_get_contents($base.'/osdr/list?limit='.$limit);
        $data  = $json ? json_decode($json, true) : ['items' => []];
        $items = $data['items'] ?? [];

        $items = $this->osdrService->flattenOsdr($items);

        $itemsDto = array_map(fn($item) => new OsdrItemDto($item), $items);
        $itemsArray = array_map(fn($dto) => $dto->toArray(), $itemsDto);

        // Filter by search if provided
        if ($search = $request->query('search')) {
            $itemsArray = array_filter($itemsArray, fn($item) => stripos($item['title'] ?? '', $search) !== false);
        }

        // Paginate with 15 records per page
        $perPage = 15;
        $currentPage = $request->query('page', 1);
        $total = count($itemsArray);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = new LengthAwarePaginator(
            array_slice($itemsArray, $offset, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'pageName' => 'page']
        );
        $paginatedItems->appends($request->query());

        return view('osdr', [
            'items' => $paginatedItems,
            'src'   => $base.'/osdr/list?limit='.$limit,
        ]);
    }
}
