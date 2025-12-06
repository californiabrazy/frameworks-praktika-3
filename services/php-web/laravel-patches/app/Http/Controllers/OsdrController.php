<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return view('osdr', [
            'items' => array_map(fn($dto) => $dto->toArray(), $itemsDto),
            'src'   => $base.'/osdr/list?limit='.$limit,
        ]);
    }
}
