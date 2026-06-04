<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Registrar un nuevo usuario',
        description: 'Crea una nueva cuenta de usuario y devuelve un token de acceso.',
        operationId: 'register',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez', description: 'Nombre completo del usuario'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com', description: 'Correo electrónico'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123', description: 'Contraseña (mínimo 8 caracteres)'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123', description: 'Confirmación de la contraseña'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario registrado exitosamente'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                            new OA\Property(property: 'email', type: 'string', example: 'juan@example.com'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ]),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456...'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'El correo electrónico ya está registrado.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'token' => $token,
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Iniciar sesión',
        description: 'Autentica un usuario y devuelve un token de acceso.',
        operationId: 'login',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com', description: 'Correo electrónico'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123', description: 'Contraseña'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inicio de sesión exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Inicio de sesión exitoso'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                            new OA\Property(property: 'email', type: 'string', example: 'juan@example.com'),
                        ]),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456...'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Credenciales inválidas'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'El correo electrónico es obligatorio.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        // Revocar tokens anteriores (opcional: mantener solo el token actual)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 200);
    }

    #[OA\Post(
        path: '/api/auth/google',
        summary: 'Iniciar sesión con Google',
        description: 'Autentica un usuario usando el ID token de Google y devuelve un token de acceso.',
        operationId: 'googleLogin',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id_token'],
                properties: [
                    new OA\Property(property: 'id_token', type: 'string', description: 'Google ID Token obtenido en la app móvil'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Inicio de sesión exitoso'),
            new OA\Response(response: 401, description: 'Token inválido')
        ]
    )]
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $idToken = $request->input('id_token');

        // Verificamos el token enviándolo directamente al endpoint de validación de Google
        $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token de Google inválido',
            ], 401);
        }

        $googleUser = $response->json();

        if (!isset($googleUser['email']) || !isset($googleUser['sub'])) {
            return response()->json([
                'message' => 'El token no contiene información válida (email/sub)',
            ], 401);
        }

        // Buscar al usuario por google_id o por email
        $user = User::where('google_id', $googleUser['sub'])
                    ->orWhere('email', $googleUser['email'])
                    ->first();

        if (!$user) {
            // Crear el usuario si no existe
            $user = User::create([
                'name' => $googleUser['name'] ?? 'Usuario',
                'email' => $googleUser['email'],
                'google_id' => $googleUser['sub'],
                'password' => null, // No requiere contraseña si usa Google
            ]);
        } else if (!$user->google_id) {
            // Vincular la cuenta existente con el ID de Google
            $user->update(['google_id' => $googleUser['sub']]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso con Google',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 200);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token de acceso del usuario autenticado.',
        operationId: 'logout',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sesión cerrada exitosamente'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No autenticado'),
                    ]
                )
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ], 200);
    }

    #[OA\Get(
        path: '/api/auth/profile',
        summary: 'Obtener perfil del usuario',
        description: 'Devuelve la información del usuario autenticado.',
        operationId: 'profile',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil del usuario',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                            new OA\Property(property: 'email', type: 'string', example: 'juan@example.com'),
                            new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No autenticado'),
                    ]
                )
            ),
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], 200);
    }

    #[OA\Put(
        path: '/api/auth/profile',
        summary: 'Actualizar perfil del usuario',
        description: 'Actualiza la información del usuario autenticado.',
        operationId: 'updateProfile',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez Actualizado', description: 'Nuevo nombre'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.nuevo@example.com', description: 'Nuevo correo electrónico'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Perfil actualizado exitosamente'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez Actualizado'),
                            new OA\Property(property: 'email', type: 'string', example: 'juan.nuevo@example.com'),
                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No autenticado'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'El correo electrónico ya está registrado.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'updated_at' => $user->updated_at,
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/api/forgot-password',
        summary: 'Solicitar restablecimiento de contraseña',
        description: 'Envía un enlace de restablecimiento de contraseña al correo electrónico del usuario.',
        operationId: 'forgotPassword',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com', description: 'Correo electrónico del usuario')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Enlace de restablecimiento enviado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Le hemos enviado por correo electrónico su enlace para restablecer la contraseña.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o correo no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No podemos encontrar un usuario con esa dirección de correo electrónico.'),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->validated()
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 200);
        }

        return response()->json(['message' => __($status)], 422);
    }

    #[OA\Post(
        path: '/api/reset-password',
        summary: 'Restablecer contraseña',
        description: 'Restablece la contraseña del usuario utilizando el token recibido por correo electrónico.',
        operationId: 'resetPassword',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'some-reset-token', description: 'Token de restablecimiento de contraseña'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com', description: 'Correo electrónico del usuario'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newpassword123', description: 'Nueva contraseña (mínimo 8 caracteres)'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newpassword123', description: 'Confirmación de la nueva contraseña')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contraseña restablecida exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Su contraseña ha sido restablecida.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o token inválido/expirado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Este token de restablecimiento de contraseña es inválido.'),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60))->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 200);
        }

        return response()->json(['message' => __($status)], 422);
    }
}
