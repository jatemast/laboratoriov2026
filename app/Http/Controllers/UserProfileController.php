<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Court;
use App\Services\ImgbbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Obtener perfil del usuario autenticado.
     */
    public function show(): JsonResponse
    {
        $user = User::with('profile')->findOrFail(auth()->id());

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Actualizar perfil del usuario.
     */
    public function update(Request $request): JsonResponse
    {
        $user = User::findOrFail(auth()->id());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'birth_date' => 'nullable|date',
            'skill_level' => 'nullable|integer|min:1|max:10',
            'preferred_hand' => 'nullable|in:left,right,ambidextrous',
            'preferences' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);

        // Actualizar datos del usuario
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['last_name'])) {
            $user->last_name = $validated['last_name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        $user->save();

        // Actualizar o crear perfil
        $profileData = array_filter($validated, function ($key) {
            return !in_array($key, ['name', 'last_name', 'email', 'phone']);
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($profileData)) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente.',
            'data' => new UserResource($user->fresh('profile')),
        ]);
    }

    /**
     * Agrega una cancha a la lista de favoritos del usuario.
     */
    #[OA\Post(
        path: "/api/profile/favorite-courts",
        summary: "Agregar cancha a favoritos",
        security: [["bearerAuth" => []]],
        tags: ["Perfil de Usuario"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["court_id"],
                properties: [
                    new OA\Property(property: "court_id", type: "integer", example: 1, description: "ID de la cancha a agregar a favoritos")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Cancha agregada a favoritos exitosamente"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Cancha no encontrada"),
            new OA\Response(response: 409, description: "La cancha ya está en favoritos"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    public function addFavoriteCourt(Request $request): JsonResponse
    {
        $request->validate(["court_id" => "required|exists:courts,id"]);

        $user = $request->user();
        $courtId = $request->court_id;

        if ($user->favoriteCourts()->where("court_id", $courtId)->exists()) {
            return response()->json(["message" => "La cancha ya está en favoritos."], 409);
        }

        $user->favoriteCourts()->attach($courtId);

        return response()->json(["message" => "Cancha agregada a favoritos exitosamente."], 200);
    }

    /**
     * Elimina una cancha de la lista de favoritos del usuario.
     */
    #[OA\Delete(
        path: "/api/profile/favorite-courts/{courtId}",
        summary: "Eliminar cancha de favoritos",
        security: [["bearerAuth" => []]],
        tags: ["Perfil de Usuario"],
        parameters: [
            new OA\Parameter(
                name: "courtId",
                in: "path",
                required: true,
                description: "ID de la cancha a eliminar de favoritos",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Cancha eliminada de favoritos exitosamente"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Cancha no encontrada en favoritos"),
        ]
    )]
    public function removeFavoriteCourt(int $courtId, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->favoriteCourts()->where("court_id", $courtId)->exists()) {
            return response()->json(["message" => "La cancha no está en favoritos."], 404);
        }

        $user->favoriteCourts()->detach($courtId);

        return response()->json(["message" => "Cancha eliminada de favoritos exitosamente."], 200);
    }

    /**
     * Obtiene la lista de canchas favoritas del usuario autenticado.
     */
    #[OA\Get(
        path: "/api/profile/favorite-courts",
        summary: "Listar canchas favoritas",
        security: [["bearerAuth" => []]],
        tags: ["Perfil de Usuario"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de canchas favoritas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/CourtResource")
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function getFavoriteCourts(Request $request): JsonResponse
    {
        $user = $request->user();
        $favoriteCourts = $user->favoriteCourts()->with("club")->get();

        return response()->json([
            "success" => true,
            "data" => CourtResource::collection($favoriteCourts),
        ]);
    }
    /**
     * Subir foto de perfil a ImgBB.
     */
    public function uploadPhoto(Request $request, ImgbbService $imgbbService): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $user = User::findOrFail(auth()->id());

        $file = $request->file('photo');
        $imagePath = $file->getPathname();

        $result = $imgbbService->upload($imagePath, 'avatar_' . $user->id . '_' . time());

        if (!$result || !isset($result['url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen a ImgBB. Intente nuevamente.',
            ], 500);
        }

        // Guardar la URL en el perfil del usuario
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['avatar_url' => $result['url']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil actualizada exitosamente.',
            'data' => [
                'avatar_url' => $result['url'],
                'thumb_url' => $result['thumb_url'] ?? null,
                'medium_url' => $result['medium_url'] ?? null,
            ],
        ]);
    }

    /**
     * Eliminar foto de perfil.
     */
    public function deletePhoto(): JsonResponse
    {
        $user = User::findOrFail(auth()->id());

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['avatar_url' => null]
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil eliminada exitosamente.',
        ]);
    }
}
