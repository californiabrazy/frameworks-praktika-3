<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\AstroService;

class AstroController extends Controller
{
    protected AstroService $astroService;

    public function __construct(AstroService $astroService)
    {
        $this->astroService = $astroService;
    }

    public function index()
    {
        return view('astro', ['events' => null]);
    }

    public function events(Request $r)
    {
        try {
            $params = $r->query();
            $response = $this->astroService->getAstroEvents($params);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }
}
