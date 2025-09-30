<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Display a listing of the themes.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $themes = Theme::all();
        return response()->json($themes);
    }

    /**
     * Store a newly created theme in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'primary_color' => 'required|string',
            'secondary_color' => 'required|string',
            'tertiary_color' => 'nullable|string',
            'button_color' => 'required|string',
            'button_text_color' => 'required|string',
            'active_navigation_color' => 'required|string',
            'inactive_navigation_color' => 'required|string',
            'add_cart_button_color' => 'required|string',
            'add_cart_text_color' => 'required|string',
            'add_cart_border_color' => 'required|string',
            'primary_background_color' => 'nullable|string',
            'secondary_background_color' => 'nullable|string',
            'third_background_color' => 'nullable|string',
            'fourth_background_color' => 'nullable|string',
            'fifth_background_color' => 'nullable|string',
            'link_color' => 'nullable|string',
            'color_6' => 'nullable|string',
            'color_7' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $theme = Theme::create($request->all());

        return response()->json(['message' => 'Theme created successfully', 'theme' => $theme]);
    }

    /**
     * Display the specified theme.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $theme = Theme::findOrFail($id);
        return response()->json($theme);
    }

    public function latest(): JsonResponse
    {
        $theme = Theme::where('status', 1)
            ->latest('created_at')
            ->first();
        if (!$theme) {
            return response()->json(['error' => 'No active theme found'], 404);
        }
        return response()->json($theme);
    }

    /**
     * Update the specified theme in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'primary_color' => 'sometimes|string',
            'secondary_color' => 'sometimes|string',
            'tertiary_color' => 'sometimes|string',
            'button_color' => 'sometimes|string',
            'button_text_color' => 'sometimes|string',
            'active_navigation_color' => 'sometimes|string',
            'inactive_navigation_color' => 'sometimes|string',
            'add_cart_button_color' => 'sometimes|string',
            'add_cart_text_color' => 'sometimes|string',
            'add_cart_border_color' => 'sometimes|string',
            'primary_background_color' => 'sometimes|string',
            'secondary_background_color' => 'sometimes|string',
            'third_background_color' => 'sometimes|string',
            'fourth_background_color' => 'sometimes|string',
            'fifth_background_color' => 'sometimes|string',
            'link_color' => 'sometimes|string',
            'color_6' => 'sometimes|string',
            'color_7' => 'sometimes|string',
            'status' => 'sometimes|boolean',
        ]);

        $theme = Theme::findOrFail($id);
        $theme->update($request->all());

        return response()->json(['message' => 'Theme updated successfully', 'theme' => $theme]);
    }

    /**
     * Remove the specified theme from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $theme = Theme::findOrFail($id);
        $theme->delete();

        return response()->json(['message' => 'Theme deleted successfully']);
    }
}
