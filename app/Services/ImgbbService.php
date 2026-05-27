<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImgbbService
{
    /**
     * URL base de la API de ImgBB.
     */
    private const API_URL = 'https://api.imgbb.com/1/upload';

    /**
     * Clave API de ImgBB.
     */
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.imgbb.key', 'd7f910676a11818a1906b1153a576736');
    }

    /**
     * Subir una imagen a ImgBB.
     *
     * @param string $imageData Contenido binario de la imagen o base64
     * @param string|null $name Nombre opcional para la imagen
     * @return array|null Datos de la respuesta de ImgBB o null si falla
     */
    public function upload(string $imageData, ?string $name = null): ?array
    {
        try {
            // Detectar si es base64 o binario
            $isBase64 = !file_exists($imageData);

            $payload = [
                'key' => $this->apiKey,
            ];

            if ($isBase64) {
                // Si es base64, enviar directamente
                $payload['image'] = $imageData;
            } else {
                // Si es un archivo, leerlo y convertirlo a base64
                $payload['image'] = base64_encode(file_get_contents($imageData));
            }

            if ($name) {
                $payload['name'] = $name;
            }

            $response = Http::asMultipart()
                ->timeout(30)
                ->post(self::API_URL, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data']['url'])) {
                    Log::info('ImgBB upload successful', [
                        'url' => $data['data']['url'],
                        'delete_url' => $data['data']['delete_url'] ?? null,
                    ]);

                    return [
                        'url' => $data['data']['url'],
                        'thumb_url' => $data['data']['thumb']['url'] ?? null,
                        'medium_url' => $data['data']['medium']['url'] ?? null,
                        'delete_url' => $data['data']['delete_url'] ?? null,
                        'display_url' => $data['data']['display_url'] ?? null,
                    ];
                }

                Log::warning('ImgBB response missing url', ['response' => $data]);
                return null;
            }

            Log::error('ImgBB upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (ConnectionException $e) {
            Log::error('ImgBB connection error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('ImgBB upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Subir una imagen desde una URL.
     *
     * @param string $url URL de la imagen a subir
     * @param string|null $name Nombre opcional
     * @return array|null
     */
    public function uploadFromUrl(string $url, ?string $name = null): ?array
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $base64 = base64_encode($response->body());
                return $this->upload($base64, $name);
            }

            Log::error('Failed to download image from URL', ['url' => $url]);
            return null;
        } catch (\Exception $e) {
            Log::error('ImgBB uploadFromUrl exception: ' . $e->getMessage());
            return null;
        }
    }
}
