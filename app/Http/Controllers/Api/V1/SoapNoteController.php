<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoapNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([]);
    }
}
