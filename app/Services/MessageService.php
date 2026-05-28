<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Facades\DB;

class MessageService
{
    /**
     * Obtener conversación entre dos usuarios.
     */
    public function conversation(int $userId1, int $userId2, array $filters = [])
    {
        $query = Message::with(['sender.profile', 'receiver.profile'])
            ->betweenUsers($userId1, $userId2);

        if (!empty($filters['match_id'])) {
            $query->forMatch($filters['match_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Obtener mensajes de un partido.
     */
    public function matchMessages(int $matchId, array $filters = [])
    {
        return Message::with(['sender.profile', 'receiver.profile'])
            ->forMatch($matchId)
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Enviar un mensaje.
     */
    public function send(int $senderId, int $receiverId, string $message, ?int $matchId = null): Message
    {
        return Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'match_id' => $matchId,
            'message' => $message,
        ]);
    }

    /**
     * Marcar mensajes como leídos.
     */
    public function markAsRead(int $messageId): ?Message
    {
        $message = Message::findOrFail($messageId);
        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return $message->fresh();
    }

    /**
     * Marcar todos los mensajes de una conversación como leídos.
     */
    public function markConversationAsRead(int $userId, int $otherUserId): void
    {
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Obtener conteo de mensajes no leídos.
     */
    public function unreadCount(int $userId): int
    {
        return Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Obtener lista de conversaciones (último mensaje con cada usuario).
     */
    public function conversations(int $userId)
    {
        $subquery = Message::select('*',
            DB::raw('ROW_NUMBER() OVER (PARTITION BY
                CASE WHEN sender_id = ' . $userId . ' THEN receiver_id ELSE sender_id END
                ORDER BY created_at DESC) as rn')
        )
        ->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('receiver_id', $userId);
        });

        return Message::fromSub($subquery, 'sub')
            ->where('rn', 1)
            ->with(['sender.profile', 'receiver.profile'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Eliminar un mensaje.
     */
    public function delete(int $messageId): bool
    {
        $message = Message::findOrFail($messageId);
        return $message->delete();
    }
}
