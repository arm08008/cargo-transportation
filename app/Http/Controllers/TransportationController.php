<?php

namespace App\Http\Controllers;

use App\Services\CargoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportationController extends Controller
{
    /**
     * @throws \Exception
     */
    public function calculateContainers(Request $request, CargoService $cargoService) :JsonResponse
    {
        // Todo request validation
        $result = $cargoService->calculate($request->all());

        return response()->json([
            '20DC' => $result[CargoService::SMALL_CONTAINER],
            '40DC' => $result[CargoService::BIG_CONTAINER],
        ]);
    }
}
