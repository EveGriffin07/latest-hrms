# Face Recognition — Key Validation Checklist (Codex Acceptance Criteria)

## Part 4 — Acceptance Criteria

### Enrollment only succeeds if

| Criterion | Implementation |
|-----------|----------------|
| **Exactly 1 face** | `face_api.py`: `validate_frame()` returns error "No face detected" or "Only one person allowed in frame" when 0 or >1 faces. |
| **Quality OK** | `validate_frame()`: face size (MIN_FACE_WIDTH), sharpness (MIN_SHARPNESS), brightness range, det_score (occlusion proxy), pose (yaw/pitch/roll ≤ MAX_POSE_DEG). |
| **Liveness OK** | `face_api.py`: `movement_score(accepted_metrics) >= MIN_LIVENESS_MOVEMENT`; otherwise enrollment rejected with "Liveness failed: move your head or blink between captures". |
| **Enough samples (≥5)** | `face_api.py`: `MIN_FRAMES = 5`, `MAX_FRAMES = 10`; request must send 5–10 images. Laravel validates `min:5, max:10`. |
| **Internal consistency OK** | `build_template()`: embeddings compared to mean; keep only those with similarity ≥ MIN_INTRA_SIM; require at least `max(3, MIN_FRAMES // 2)` to build template. Otherwise "Captures are inconsistent. Re-capture with better quality." |

### Attendance only succeeds if

| Criterion | Implementation |
|-----------|----------------|
| **Face validation OK** | Same `validate_frame()` in `face_api.py`; `/attend` accepts 1–3 frames and uses best valid frame. |
| **Liveness OK** | Attendance uses 1–3 frames; validation includes quality/pose. (Optional stronger liveness can be added.) |
| **Similarity ≥ threshold** | `cosine(q, t) >= threshold` (config: `FACE_API_THRESHOLD`, default 0.35). |
| **Policy checks OK** | **Duplicate punch**: no repeated CHECK_IN without CHECK_OUT; no repeated CHECK_OUT without CHECK_IN today. **Cooldown**: no scan within `cooldown_minutes` of last success. **Optional**: location/device (not enforced in baseline). |

---

## Where to verify in code

- **Enrollment**: `face_api.py` (`validate_frame`, `build_template`, `/enroll`), `EmployeeFaceController::enroll` (validation rules).
- **Attendance**: `face_api.py` (`/attend`), `EmployeeFaceAttendanceController::check` (policy + session + atomic write).
