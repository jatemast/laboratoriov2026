<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ClubAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ==========================================
// RUTAS PÚBLICAS
// ==========================================

// Auth público (compatibilidad con Android que llama a auth/register, auth/login)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

// Recuperación de Contraseña
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Clubs públicos
Route::get('clubs', [ClubController::class, 'index']);
Route::get('clubs/slug/{slug}', [ClubController::class, 'showBySlug']);
Route::get('clubs/{id}', [ClubController::class, 'show']);

// Canchas públicas
Route::get('clubs/{clubId}/courts', [CourtController::class, 'index']);
Route::get('courts/{id}', [CourtController::class, 'show']);
Route::get('courts/{id}/availability', [CourtController::class, 'checkAvailability']);
Route::get('courts/{id}/available-slots', [CourtController::class, 'availableSlots']);

// Reseñas públicas
Route::get('reviews', [ReviewController::class, 'index']);

// Partidos públicos
Route::get('matches', [MatchController::class, 'index']);
Route::get('matches/{id}', [MatchController::class, 'show']);

// ==========================================
// RUTAS PROTEGIDAS (Requieren autenticación Sanctum)
// ==========================================

Route::middleware('auth:sanctum')->group(function () {

    // ========== AUTENTICACIÓN (compatibilidad Android) ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::get('auth/user-profile', [UserProfileController::class, 'show']);

    // ========== PERFIL DE USUARIO ==========
    Route::get('profile', [UserProfileController::class, 'show']);
    Route::put('profile', [UserProfileController::class, 'update']);

    // ========== FOTO DE PERFIL (ImgBB) ==========
    Route::post('profile/upload-photo', [UserProfileController::class, 'uploadPhoto']);
    Route::delete('profile/photo', [UserProfileController::class, 'deletePhoto']);

    // ========== CANCHAS FAVORITAS ==========
    Route::get('profile/favorite-courts', [UserProfileController::class, 'getFavoriteCourts']);
    Route::post('profile/favorite-courts', [UserProfileController::class, 'addFavoriteCourt']);
    Route::delete('profile/favorite-courts/{courtId}', [UserProfileController::class, 'removeFavoriteCourt']);

    // ========== CLUBS (Protegidas) ==========
    Route::post('clubs', [ClubController::class, 'store']);
    Route::put('clubs/{id}', [ClubController::class, 'update']);
    Route::delete('clubs/{id}', [ClubController::class, 'destroy']);
    Route::get('clubs/{clubId}/members', [ClubController::class, 'members']);
    Route::post('clubs/{clubId}/members', [ClubController::class, 'addMember']);

    // ========== CANCHAS (Protegidas) ==========
    Route::post('clubs/{clubId}/courts', [CourtController::class, 'store']);
    Route::put('courts/{id}', [CourtController::class, 'update']);
    Route::delete('courts/{id}', [CourtController::class, 'destroy']);

    // ========== RESERVAS ==========
    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/{id}', [BookingController::class, 'show']);
    Route::post('bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('bookings/{bookingId}/invite', [BookingController::class, 'invitePlayer']);
    Route::post('bookings/{bookingId}/accept-invitation', [BookingController::class, 'acceptInvitation']);
    Route::post('bookings/{bookingId}/decline-invitation', [BookingController::class, 'declineInvitation']);

    // ========== PARTIDOS ==========
    Route::post('matches', [MatchController::class, 'store']);
    Route::put('matches/{id}/score', [MatchController::class, 'updateScore']);
    Route::post('matches/{matchId}/join', [MatchController::class, 'join']);
    Route::post('matches/{matchId}/leave', [MatchController::class, 'leave']);
    Route::get('matchmaking/opponents', [MatchController::class, 'findOpponents']);

    // ========== PAGOS ==========
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('bookings/{bookingId}/pay', [PaymentController::class, 'process']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    Route::post('payments/{id}/refund', [PaymentController::class, 'refund']);

    // ========== NOTIFICACIONES ==========
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

    // ========== RESEÑAS ==========
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);

    // ========== PANEL DE ADMINISTRACIÓN DEL CLUB ==========
    Route::get('admin/clubs/{clubId}/summary', [ClubAdminController::class, 'getDashboardSummary']);
    Route::get('admin/clubs/{clubId}/upcoming-bookings', [ClubAdminController::class, 'getUpcomingBookings']);
});
