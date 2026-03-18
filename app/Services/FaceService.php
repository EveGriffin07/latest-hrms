<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class FaceService
{
    private string $baseUrl;
    private int $timeout = 25;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.face_api.url', ''), '/');
    }

    public function enroll(int $employeeId, array $images, ?string $sessionId = null, ?string $deviceId = null): array
    {
        $request = Http::timeout($this->timeout)->acceptJson();

        foreach ($images as $idx => $image) {
            /** @var UploadedFile $image */
            $request = $request->attach("images", file_get_contents($image->getRealPath()), $image->getClientOriginalName());
        }

        $payload = ['employee_id' => $employeeId];
        if ($sessionId !== null) {
            $payload['session_id'] = $sessionId;
        }
        if ($deviceId !== null) {
            $payload['device_id'] = $deviceId;
        }
        $response = $request->post($this->baseUrl.'/enroll', $payload);

        if ($response->failed()) {
            $message = $this->error($response);
            $json = $response->json();
            if (is_array($json) && ! empty($json['frames'])) {
                $firstReason = null;
                foreach ($json['frames'] as $frame) {
                    if (isset($frame['accepted']) && $frame['accepted'] === false && ! empty($frame['reason'])) {
                        $firstReason = $frame['reason'];
                        break;
                    }
                }
                if ($firstReason !== null) {
                    $message = $firstReason . ' Use a clear, well-lit photo, one person, face centered and close enough.';
                }
            }
            return ['ok' => false, 'message' => $message];
        }

        return [
            'ok' => true,
            'embeddings' => $response->json('embeddings', []),
            'template' => $response->json('template_embedding') ?? $response->json('embedding'),
            'frames' => $response->json('frames', []),
            'liveness' => $response->json('liveness'),
            'quality' => $response->json('quality'),
            'model_version' => $response->json('model_version') ?? $response->json('model', 'buffalo_l'),
            'enrollment_quality_score' => $response->json('enrollment_quality_score'),
        ];
    }

    /**
     * Attendance scan: 1–3 frames, validate + match vs stored template.
     * Returns ok, matched, score, failure_reason (NO_FACE, MULTI_FACE, LOW_QUALITY, LIVENESS_FAIL, BELOW_THRESHOLD), message.
     */
    public function attend(int $employeeId, array $frames, array|string $storedEmbedding): array
    {
        $embeddingString = is_string($storedEmbedding) ? $storedEmbedding : json_encode($storedEmbedding);
        if (! $embeddingString) {
            return ['ok' => false, 'message' => 'Stored embedding is missing.', 'failure_reason' => 'LOW_QUALITY'];
        }

        $request = Http::timeout($this->timeout)->acceptJson();
        foreach (array_slice($frames, 0, 3) as $idx => $file) {
            /** @var UploadedFile $file */
            $request = $request->attach('images', file_get_contents($file->getRealPath()), $file->getClientOriginalName() ?: "frame_{$idx}.jpg");
        }

        $response = $request->post($this->baseUrl.'/attend', [
            'user_id' => (string) $employeeId,
            'stored_embedding' => $embeddingString,
            'threshold' => config('services.face_api.threshold', 0.35),
        ]);

        if ($response->failed()) {
            $body = $response->json();
            return [
                'ok' => false,
                'message' => $body['error'] ?? $this->error($response),
                'failure_reason' => $body['failure_reason'] ?? 'LOW_QUALITY',
            ];
        }

        $failureReason = $response->json('failure_reason');
        $matched = (bool) $response->json('matched');
        $message = $matched ? null : $this->attendFailureMessage($failureReason, $response->json('score'));

        return [
            'ok' => true,
            'matched' => $matched,
            'score' => $response->json('score'),
            'failure_reason' => $failureReason,
            'message' => $message,
        ];
    }

    /**
     * STEP 2 — Process one frame: face validation + movement state machine. Returns movement_state, valid_frames, message, session_completed.
     */
    public function processFrame(
        int $employeeId,
        UploadedFile $image,
        string $sessionId,
        ?array $guideBox = null
    ): array {
        $request = Http::timeout($this->timeout)
            ->acceptJson()
            ->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName() ?: 'frame.jpg');

        $payload = [
            'session_id' => $sessionId,
            'employee_id' => (string) $employeeId,
        ];
        if ($guideBox && count($guideBox) === 4) {
            $payload['guide_x_min'] = $guideBox[0];
            $payload['guide_y_min'] = $guideBox[1];
            $payload['guide_x_max'] = $guideBox[2];
            $payload['guide_y_max'] = $guideBox[3];
        }

        $response = $request->post($this->baseUrl.'/enroll/frame', $payload);

        if ($response->failed()) {
            return [
                'ok' => false,
                'reason' => $response->json('reason') ?? $this->error($response),
                'movement_state' => 'CENTER_REQUIRED',
                'valid_frames' => 0,
                'message' => 'Validation failed.',
                'session_completed' => false,
            ];
        }

        return [
            'ok' => true,
            'accepted' => (bool) $response->json('accepted'),
            'reason' => $response->json('reason'),
            'failure_code' => $response->json('failure_code'),
            'guidance' => $response->json('guidance'),
            'movement_state' => $response->json('movement_state', 'CENTER_REQUIRED'),
            'valid_frames' => (int) $response->json('valid_frames', 0),
            'message' => $response->json('message', 'Please look straight at the camera.'),
            'session_completed' => (bool) $response->json('session_completed'),
            'timeout' => (bool) $response->json('timeout'),
        ];
    }

    /**
     * STEP 5 — Finalize enrollment (template from captured frames, consistency check). Returns template or error.
     */
    public function finalizeEnrollment(int $employeeId, string $sessionId): array
    {
        $response = Http::timeout($this->timeout)->acceptJson()->post($this->baseUrl.'/enroll/finalize', [
            'session_id' => $sessionId,
            'employee_id' => (string) $employeeId,
        ]);

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('error') ?? $this->error($response),
                'failure_reason' => $response->json('failure_reason'),
            ];
        }

        return [
            'ok' => true,
            'template' => $response->json('template_embedding') ?? $response->json('embedding'),
            'model_version' => $response->json('model_version') ?? $response->json('model', 'buffalo_l'),
            'enrollment_quality_score' => $response->json('enrollment_quality_score'),
            'valid_frames' => $response->json('valid_frames'),
        ];
    }

    /**
     * Validate one frame for the current movement step (1–5). Returns accepted, reason, next_step, embedding.
     */
    public function validateStep(
        int $employeeId,
        UploadedFile $image,
        int $currentStep,
        ?string $sessionId = null,
        ?array $guideBox = null
    ): array {
        $request = Http::timeout($this->timeout)
            ->acceptJson()
            ->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName() ?: 'frame.jpg');

        $payload = [
            'current_step' => $currentStep,
            'employee_id' => (string) $employeeId,
            'session_id' => $sessionId,
        ];
        if ($guideBox && count($guideBox) === 4) {
            $payload['guide_x_min'] = $guideBox[0];
            $payload['guide_y_min'] = $guideBox[1];
            $payload['guide_x_max'] = $guideBox[2];
            $payload['guide_y_max'] = $guideBox[3];
        }

        $response = $request->post($this->baseUrl.'/enroll/validate-step', $payload);

        if ($response->failed()) {
            return [
                'ok' => false,
                'accepted' => false,
                'reason' => $this->error($response),
                'next_step' => $currentStep,
            ];
        }

        return [
            'ok' => true,
            'accepted' => (bool) $response->json('accepted'),
            'reason' => $response->json('reason'),
            'next_step' => (int) $response->json('next_step', $currentStep),
            'embedding' => $response->json('embedding'),
        ];
    }

    /**
     * Complete enrollment with exactly 5 embeddings (from 5 movement steps). Returns template, quality, model_version.
     */
    public function enrollComplete(int $employeeId, ?string $sessionId, array $embeddings): array
    {
        $response = Http::timeout($this->timeout)->acceptJson()->post($this->baseUrl.'/enroll/complete', [
            'employee_id' => (string) $employeeId,
            'session_id' => $sessionId,
            'embeddings' => json_encode($embeddings),
        ]);

        if ($response->failed()) {
            return ['ok' => false, 'message' => $response->json('error') ?? $this->error($response)];
        }

        return [
            'ok' => true,
            'template' => $response->json('template_embedding') ?? $response->json('embedding'),
            'model_version' => $response->json('model_version') ?? $response->json('model', 'buffalo_l'),
            'enrollment_quality_score' => $response->json('enrollment_quality_score'),
            'quality' => $response->json('quality'),
        ];
    }

    public function match(int $employeeId, UploadedFile $frame, array|string $storedEmbedding): array
    {
        $embeddingString = is_string($storedEmbedding) ? $storedEmbedding : json_encode($storedEmbedding);

        if (! $embeddingString) {
            return ['ok' => false, 'message' => 'Stored embedding is missing.'];
        }

        $request = Http::timeout($this->timeout)
            ->acceptJson()
            ->attach('image', file_get_contents($frame->getRealPath()), $frame->getClientOriginalName());

        $response = $request->post($this->baseUrl.'/match', [
            'employee_id' => $employeeId,
            'stored_embedding' => $embeddingString,
            'threshold' => config('services.face_api.threshold', 0.35),
        ]);

        if ($response->failed()) {
            return ['ok' => false, 'message' => $this->error($response)];
        }

        return [
            'ok' => true,
            'matched' => $response->json('matched'),
            'score' => $response->json('score'),
        ];
    }

    private function attendFailureMessage(?string $reason, $score): string
    {
        return match ($reason) {
            'NO_FACE' => 'No face detected. Position your face in the frame and try again.',
            'MULTI_FACE' => 'Only one person should be in frame. Try again.',
            'LOW_QUALITY' => 'Face not clear (blur, lighting, or pose). Try again.',
            'LIVENESS_FAIL' => 'Liveness check failed. Try again.',
            'BELOW_THRESHOLD' => 'Face did not match. Please try again.',
            default => 'Verification failed. Score: '.($score !== null ? number_format($score, 3) : '—').'. Try again.',
        };
    }

    private function error($response): string
    {
        $json = $response->json();
        if (is_array($json) && isset($json['error'])) {
            return $json['error'];
        }
        return $response->reason();
    }
}
