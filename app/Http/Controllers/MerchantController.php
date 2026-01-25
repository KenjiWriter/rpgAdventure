<?php

namespace App\Http\Controllers;

use App\Services\MerchantService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    public function index(Request $request)
    {
        // Fallback or explicit fetch if middleware doesn't inject
        // The previous middleware might check existance but not merge into request
        $character = auth()->user()->characters()->first();

        if (!$character) {
            return redirect()->route('character.create');
        }

        $stock = $this->merchantService->getStock($character);

        if ($request->wantsJson()) {
            return response()->json(['stock' => $stock]);
        }

        return Inertia::render('Merchant', [
            'characterId' => $character->id,
            'stock' => $stock
        ]);
    }

    public function refresh(Request $request)
    {
        $character = auth()->user()->characters()->first();

        try {
            $stock = $this->merchantService->refreshStockManual($character);
            return response()->json([
                'stock' => $stock,
                'gold' => $character->refresh()->gold
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function buy(Request $request)
    {
        $request->validate([
            'merchant_item_id' => 'required|string',
        ]);

        $character = auth()->user()->characters()->first();

        try {
            $this->merchantService->buyItem($character, $request->input('merchant_item_id'));
            return response()->json([
                'success' => true,
                'gold' => $character->refresh()->gold
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
