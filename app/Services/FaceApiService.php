<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

class FaceApiService
{
    private string $baseUrl;
    private float $threshold;
    private string $model;
    private int $timeoutSeconds = 25;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.face_api.url', ''), '/');
        $this->threshold = (float) config('services.face_api.threshold', 0.35);
        $this->model = (string) config('services.face_api.model', 'buffalo_l');
    }

    public function enroll(int|string $employeeId, UploadedFile $image): array
    {
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->attach('image', $this->fileContents($image), $image->getClientOriginalName())
                ->post($this->baseUrl . '/enroll', [
                    'employee_id' => $employeeId,
                ]);

            if ($response->failed()) {
                $body = $response->json();
                return [
                    'ok' => false,
                    'message' => $this->extractError($response),
                    'failure_reason' => is_array($body) ? ($body['failure_reason'] ?? null) : null,
                    'status' => $response->status(),
                ];
            }

            $payload = $response->json();

            return [
                'ok' => (bool) ($payload['ok'] ?? false),
                'embedding' => $payload['embedding'] ?? null,
                'model' => $this->model,
                'message' => $payload['error'] ?? 'Face enrolled successfully.',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verify(int|string $employeeId, array|string $storedEmbedding, UploadedFile $image): array
    {
        $embeddingString = is_string($storedEmbedding)
            ? $storedEmbedding
            : json_encode($storedEmbedding);

        if (! $embeddingString) {
            return [
                'ok' => false,
                'message' => 'Stored embedding is empty or invalid.',
            ];
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->attach('image', $this->fileContents($image), $image->getClientOriginalName())
                ->post($this->baseUrl . '/verify', [
                    'user_id' => $employeeId,
                    'stored_embedding' => $embeddingString,
                    'threshold' => $this->threshold,
                ]);

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'message' => $this->extractError($response),
                    'status' => $response->status(),
                ];
            }

            $payload = $response->json();
            $matched = (bool) ($payload['matched'] ?? false);

            return [
                'ok' => (bool) ($payload['ok'] ?? false),
                'matched' => $matched,
                'score' => $payload['score'] ?? null,
                'threshold' => $payload['threshold'] ?? $this->threshold,
                'message' => $payload['error'] ?? ($matched ? 'Face verified successfully.' : 'Face did not match.'),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify with 1–3 frames (best score wins). Uses the FastAPI /verify handler (images[]).
     *
     * @param UploadedFile[] $images
     */
    public function verifyMany(int|string $employeeId, array|string $storedEmbedding, array $images): array
    {
        $embeddingString = is_string($storedEmbedding)
            ? $storedEmbedding
            : json_encode($storedEmbedding);

        if (! $embeddingString) {
            return [
                'ok' => false,
                'message' => 'Stored embedding is empty or invalid.',
            ];
        }

        try {
            $req = Http::timeout($this->timeoutSeconds)->acceptJson();
            foreach (array_slice($images, 0, 3) as $idx => $img) {
                if (!($img instanceof UploadedFile)) continue;
                $req = $req->attach('images', $this->fileContents($img), $img->getClientOriginalName() ?: "frame_{$idx}.jpg");
            }

            $response = $req->post($this->baseUrl . '/verify', [
                'user_id' => $employeeId,
                'stored_embedding' => $embeddingString,
                'threshold' => $this->threshold,
            ]);

            if ($response->failed()) {
                $body = $response->json();
                return [
                    'ok' => false,
                    'message' => $this->extractError($response),
                    'failure_reason' => is_array($body) ? ($body['failure_reason'] ?? null) : null,
                    'status' => $response->status(),
                ];
            }

            $payload = $response->json();
            $matched = (bool) ($payload['matched'] ?? false);

            return [
                'ok' => (bool) ($payload['ok'] ?? false),
                'matched' => $matched,
                'score' => $payload['score'] ?? null,
                'threshold' => $payload['threshold'] ?? $this->threshold,
                'message' => $payload['error'] ?? ($matched ? 'Face verified successfully.' : 'Face did not match.'),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function fileContents(UploadedFile $image): string
    {
        return (string) file_get_contents($image->getRealPath());
    }

    private function extractError($response): string
    {
        $json = $response->json();

        if (is_array($json) && isset($json['error'])) {
            return (string) $json['error'];
        }

        return (string) ($response->reason() ?: 'Face API request failed');
    }
}
