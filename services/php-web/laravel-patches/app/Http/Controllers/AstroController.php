<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\AstroService;
use App\Http\Support\JwstHelper;
use App\Http\DTO\JwstImageDto;

class AstroController extends Controller
{
    protected AstroService $astroService;
    protected JwstHelper $jwstHelper;

    public function __construct(AstroService $astroService, JwstHelper $jwstHelper)
    {
        $this->astroService = $astroService;
        $this->jwstHelper = $jwstHelper;
    }

    public function index()
    {
        $jwstData = $this->jwstHelper->get('images', ['limit' => 1]);
        $imageUrl = JwstHelper::pickImageUrl($jwstData);
        $jwstImageDto = new JwstImageDto(['url' => $imageUrl, 'description' => 'JWST Image']);

        return view('astro', ['events' => null, 'jwstImage' => $jwstImageDto->toArray()]);
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
