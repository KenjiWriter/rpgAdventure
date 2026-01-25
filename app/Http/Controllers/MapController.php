<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Map;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Map::all());
    }
}
