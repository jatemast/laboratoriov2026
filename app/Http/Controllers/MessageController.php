<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Obtener conversación entre el usuario autenticado y otro usuario.
     */
    public function conversation(Request $request, int $userId): JsonResponse
    {
        $messages = $this->messageService->conversation(
            auth()->id(),
            $userId,
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => MessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Obtener mensajes de un partido.
     */
    public function matchMessages(Request $request, int $matchId): JsonResponse
    {
        $messages = $this->messageService->matchMessages(
            $matchId,
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => MessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Enviar un mensaje.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
            'match_id' => 'nullable|exists:matches,id',
        ]);

        $message = $this->messageService->send(
            auth()->id(),
            $validated['receiver_id'],
            $validated['message'],
            $validated['match_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado.',
            'data' => new MessageResource($message),
        ], 201);
    }

    /**
     * Marcar mensaje como leído.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $message = $this->messageService->markAsRead($id);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje marcado como leído.',
            'data' => new MessageResource($message),
        ]);
    }

    /**
     * Marcar conversación como leída.
     */
    public function markConversationAsRead(int $userId): JsonResponse
    {
        $this->messageService->markConversationAsRead(auth()->id(), $userId);

        return response()->json([
            'success' => true,
            'message' => 'Conversación marcada como leída.',
        ]);
    }

    /**
     * Obtener conteo de mensajes no leídos.
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->messageService->unreadCount(auth()->id());

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    /**
     * Obtener lista de conversaciones.
     */
    public function conversations(): JsonResponse
    {
        $conversations = $this->messageService->conversations(auth()->id());

        return response()->json([
            'success' => true,
            'data' => MessageResource::collection($conversations),
        ]);
    }

    /**
     * Eliminar un mensaje.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->messageService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje eliminado.',
        ]);
    }
}
