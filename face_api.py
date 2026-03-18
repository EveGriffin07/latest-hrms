from fastapi import FastAPI, UploadFile, File, Form
from fastapi.responses import JSONResponse
import numpy as np
import cv2
import ast
import time
import uuid
import math
import os
from typing import List, Optional, Tuple

from insightface.app import FaceAnalysis
from insightface.utils import face_align

app = FastAPI(title="ArcFace HRMS API")

# --- Model bootstrap (once) ---
face_app = FaceAnalysis(name="buffalo_l")
# ctx_id=0 keeps GPU if available; fall back to CPU automatically.
face_app.prepare(ctx_id=0, det_size=(640, 640))

# --- Tunable thresholds (env override supported) ---
MIN_FRAMES = 5
MAX_FRAMES = 10
MIN_FACE_WIDTH = int(os.getenv("FACE_MIN_FACE_WIDTH", "100"))
MIN_SHARPNESS = float(os.getenv("FACE_MIN_SHARPNESS", "50.0"))          # variance of Laplacian
MIN_BRIGHTNESS = float(os.getenv("FACE_MIN_BRIGHTNESS", "50.0"))        # mean grayscale
MAX_BRIGHTNESS = float(os.getenv("FACE_MAX_BRIGHTNESS", "220.0"))
MAX_POSE_DEG = float(os.getenv("FACE_MAX_POSE_DEG", "30.0"))            # verify/attend tolerance
MIN_DET_SCORE = float(os.getenv("FACE_MIN_DET_SCORE", "0.45"))          # occlusion / low-quality proxy
MIN_INTRA_SIM = 0.25          # internal similarity threshold for template building
MIN_LIVENESS_MOVEMENT = 6.0   # pixels of movement across captures (motion consistency)

# ArcFace model identifier for storage (embedding dimension = 512 for buffalo_l)
MODEL_NAME = "buffalo_l"
MODEL_VERSION = "buffalo_l"   # replace with model hash if you version embeddings

# Enrollment: look at center only (single step)
MOVEMENT_STEPS = ["center"]

# --- Movement state machine (1 step: center only) ---
CENTER_REQUIRED = "CENTER_REQUIRED"
COMPLETED = "COMPLETED"

MOVEMENT_STATE_ORDER = [CENTER_REQUIRED, COMPLETED]

# Stability: hold center pose ≥ 0.8s (~4 frames at ~5 fps)
STABILITY_FRAMES = 4
# Capture frames when center stable (total 5–6)
CAPTURE_COUNT_CENTER = 5
# Min total valid frames for success
MIN_REQUIRED_VALID_FRAMES = 5
# Internal consistency: mean pairwise similarity
MIN_INTERNAL_SIM_CONSISTENCY = 0.5
# Session timeout (seconds) — 10–15 s for center-only enrollment
ENROLL_TIMEOUT_SEC = 15

# Pose: yaw / pitch / roll ±15° for center
CENTER_YAW_PITCH_RANGE = 15.0
CENTER_ROLL_RANGE = 15.0

# In-memory session state (use Redis/DB in production)
_enroll_sessions: dict = {}


# --- Helpers ---
def read_image(file_bytes: bytes):
    nparr = np.frombuffer(file_bytes, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    return img


def variance_of_laplacian(gray: np.ndarray) -> float:
    return float(cv2.Laplacian(gray, cv2.CV_64F).var())


def estimate_pose(kps: np.ndarray) -> Tuple[float, float, float]:
    """
    Approximate yaw / pitch / roll using 5 keypoints (eyes, nose, mouth corners).
    """
    left_eye, right_eye, nose, left_mouth, right_mouth = kps
    eye_center = (left_eye + right_eye) / 2.0
    mouth_center = (left_mouth + right_mouth) / 2.0

    # roll: angle of eye line
    roll = math.degrees(math.atan2(right_eye[1] - left_eye[1], right_eye[0] - left_eye[0] + 1e-6))

    # yaw: horizontal offset of nose vs eye center, normalised by eye distance
    eye_dist = np.linalg.norm(right_eye - left_eye) + 1e-6
    yaw = math.degrees(math.asin((nose[0] - eye_center[0]) / eye_dist))

    # pitch: vertical offset of nose between eyes and mouth
    vertical_span = (mouth_center[1] - eye_center[1]) + 1e-6
    pitch = math.degrees(math.atan2(nose[1] - eye_center[1], vertical_span))

    return yaw, pitch, roll


def cosine_similarity(a: np.ndarray, b: np.ndarray) -> float:
    return float(np.dot(a, b))


def align_and_embed(img_bgr: np.ndarray, face) -> Tuple[np.ndarray, np.ndarray]:
    """
    Align a face to 112x112 crop and return (embedding, aligned_image).
    """
    aligned = face_align.norm_crop(img_bgr, face.kps, image_size=112)
    # Use embedding from FaceAnalysis (already L2-normalized after manual norm).
    emb = face.embedding.astype(np.float32)
    emb = emb / np.linalg.norm(emb)
    return emb, aligned


def _face_inside_guide(bbox: np.ndarray, img_shape: Tuple[int, ...], guide_box: Optional[Tuple[float, float, float, float]]) -> bool:
    """Check face bbox center is inside guide_box (x_min_frac, y_min_frac, x_max_frac, y_max_frac)."""
    if guide_box is None:
        return True
    h, w = img_shape[:2]
    cx = (bbox[0] + bbox[2]) / 2.0
    cy = (bbox[1] + bbox[3]) / 2.0
    x0, y0, x1, y1 = guide_box
    if x0 < 0 or x1 > 1 or y0 < 0 or y1 > 1:
        return True
    return (w * x0 <= cx <= w * x1) and (h * y0 <= cy <= h * y1)


def validate_frame_basic(
    img_bgr: np.ndarray,
    guide_box: Optional[Tuple[float, float, float, float]] = None,
    skip_pose_strict: bool = False,
) -> Tuple[Optional[np.ndarray], dict, Optional[str], Optional[str]]:
    """
    Basic face validation: 1 face, size, blur, lighting, landmark confidence.
    Returns (embedding, metrics, error_message, failure_code). On success error_message and failure_code are None.
    """
    faces = face_app.get(img_bgr)
    if not faces:
        return None, {}, "No face detected", "no_face"
    if len(faces) > 1:
        return None, {}, "Only one person allowed in frame", "multiple_faces"

    face = faces[0]
    bbox = face.bbox.astype(int)
    width = bbox[2] - bbox[0]
    height = bbox[3] - bbox[1]
    det_score = float(getattr(face, "det_score", 1.0))

    if width < MIN_FACE_WIDTH or height < MIN_FACE_WIDTH:
        return None, {}, "Face is too small or too far", "face_too_small"

    if not _face_inside_guide(bbox, img_bgr.shape, guide_box):
        return None, {}, "Face outside guide box", "face_outside_guide"

    crop = img_bgr[max(0, bbox[1]): bbox[3], max(0, bbox[0]): bbox[2]]
    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    sharpness = variance_of_laplacian(gray)
    brightness = float(np.mean(gray))

    if sharpness < MIN_SHARPNESS:
        return None, {}, "Image too blurry", "too_blurry"
    if brightness < MIN_BRIGHTNESS:
        return None, {}, "Lighting too dark", "too_dark"
    if brightness > MAX_BRIGHTNESS:
        return None, {}, "Lighting too bright", "too_bright"
    if det_score < MIN_DET_SCORE:
        return None, {}, "Landmark confidence too low", "low_confidence"

    yaw, pitch, roll = estimate_pose(face.kps)
    pose_deg = max(abs(yaw), abs(pitch), abs(roll))
    center_deg = CENTER_YAW_PITCH_RANGE if skip_pose_strict else max(CENTER_YAW_PITCH_RANGE, CENTER_ROLL_RANGE)
    if not skip_pose_strict and pose_deg > center_deg:
        return None, {}, "Keep your face centered (yaw/pitch/roll exceeded)", "face_not_centered"

    emb, aligned = align_and_embed(img_bgr, face)
    metrics = {
        "bbox": [int(x) for x in bbox],
        "sharpness": sharpness,
        "brightness": brightness,
        "yaw": yaw,
        "pitch": pitch,
        "roll": roll,
        "det_score": det_score,
        "center": [float((bbox[0] + bbox[2]) / 2.0), float((bbox[1] + bbox[3]) / 2.0)],
    }
    return emb, metrics, None, None


def validate_frame(img_bgr: np.ndarray) -> Tuple[Optional[np.ndarray], dict, Optional[str]]:
    """Run detection + quality gates (including strict pose); return (embedding, metrics, error)."""
    emb, metrics, err, _ = validate_frame_basic(img_bgr, guide_box=None, skip_pose_strict=False)
    return emb, metrics, err


def validate_frame_relaxed_pose(img_bgr: np.ndarray) -> Tuple[Optional[np.ndarray], dict, Optional[str], Optional[str]]:
    """
    Relaxed validator for live verification/attendance:
    - Keep all quality gates (one face, size, blur, lighting, det_score)
    - Allow more head pose than strict enrollment checks
    Returns (embedding, metrics, error_message, failure_code).
    """
    emb, metrics, err, failure_code = validate_frame_basic(img_bgr, guide_box=None, skip_pose_strict=True)
    if err:
        return None, {}, err, failure_code

    yaw = float(metrics.get("yaw", 0.0))
    pitch = float(metrics.get("pitch", 0.0))
    roll = float(metrics.get("roll", 0.0))
    pose_deg = max(abs(yaw), abs(pitch), abs(roll))
    if pose_deg > MAX_POSE_DEG:
        return None, {}, "Keep your face more centered (yaw/pitch/roll exceeded)", "face_not_centered"

    return emb, metrics, None, None


def check_movement_for_step(step: int, yaw: float, pitch: float, roll: float) -> Tuple[bool, Optional[str]]:
    """Return (satisfied, reason_if_not). Step 1–5 correspond to MOVEMENT_STEPS."""
    if step < 1 or step > len(MOVEMENT_STEPS):
        return False, "Invalid step"
    if step == 1:  # center
        if max(abs(yaw), abs(pitch), abs(roll)) <= MOVEMENT_POSE_CENTER_DEG:
            return True, None
        return False, "Look at the camera (center)"
    if step == 2:  # turn left
        if yaw <= -MOVEMENT_POSE_TURN_DEG:
            return True, None
        return False, "Turn your head left"
    if step == 3:  # turn right
        if yaw >= MOVEMENT_POSE_TURN_DEG:
            return True, None
        return False, "Turn your head right"
    if step == 4:  # look up
        if pitch <= -MOVEMENT_POSE_TURN_DEG:
            return True, None
        return False, "Look up"
    if step == 5:  # blink — accept neutral pose (true blink would need eye landmarks)
        if max(abs(yaw), abs(pitch), abs(roll)) <= MOVEMENT_POSE_CENTER_DEG:
            return True, None
        return False, "Face the camera and blink"
    return False, "Invalid step"


def build_template(embeddings: List[np.ndarray]) -> Tuple[Optional[np.ndarray], List[float]]:
    """
    Build a robust template by removing outliers (low similarity to mean) then averaging.
    """
    if not embeddings:
        return None, []

    matrix = np.vstack(embeddings)
    mean_vec = matrix.mean(axis=0)
    mean_vec = mean_vec / np.linalg.norm(mean_vec)

    sims = matrix @ mean_vec
    keep_mask = sims >= MIN_INTRA_SIM

    if keep_mask.sum() < max(3, MIN_FRAMES // 2):
        return None, sims.tolist()

    filtered = matrix[keep_mask]
    template = filtered.mean(axis=0)
    template = template / np.linalg.norm(template)
    return template, sims.tolist()


def movement_score(metrics: List[dict]) -> float:
    if len(metrics) < 2:
        return 0.0
    centers = np.array([m["center"] for m in metrics], dtype=np.float32)
    deltas = np.linalg.norm(np.diff(centers, axis=0), axis=1)
    return float(np.mean(deltas))


def _pose_satisfies_state(state: str, yaw: float, pitch: float, roll: float) -> bool:
    """Check if current pose satisfies the required state (center only). yaw/pitch/roll ±15°."""
    if state == CENTER_REQUIRED:
        return (
            abs(yaw) <= CENTER_YAW_PITCH_RANGE
            and abs(pitch) <= CENTER_YAW_PITCH_RANGE
            and abs(roll) <= CENTER_ROLL_RANGE
        )
    return False


def _ui_message_for_state(state: str) -> str:
    if state == CENTER_REQUIRED:
        return "Please look straight at the camera."
    if state == COMPLETED:
        return "Enrollment complete."
    return "Follow the prompt."


def _guidance_for_failure_code(failure_code: Optional[str], capturing: bool) -> str:
    """Short guidance for UI: place face, look straight, move closer, hold still, etc."""
    if capturing:
        return "Hold still…"
    if not failure_code:
        return "Look straight at the camera."
    guidance_map = {
        "no_face": "Place your face inside the frame.",
        "multiple_faces": "Only one person in frame.",
        "face_outside_guide": "Place your face inside the frame.",
        "face_too_small": "Move closer.",
        "face_not_centered": "Look straight at the camera.",
        "too_blurry": "Keep the camera steady.",
        "too_dark": "Move to better lighting.",
        "too_bright": "Reduce glare.",
        "low_confidence": "Look straight at the camera.",
        "timeout": "Timeout, please try again.",
    }
    return guidance_map.get(failure_code, "Look straight at the camera.")


def _internal_consistency_pass(embeddings: List[np.ndarray]) -> Tuple[bool, float]:
    """Check average pairwise cosine similarity > MIN_INTERNAL_SIM_CONSISTENCY."""
    if len(embeddings) < 2:
        return True, 1.0
    vectors = np.vstack(embeddings)
    vectors = vectors / np.linalg.norm(vectors, axis=1, keepdims=True)
    n = len(vectors)
    total = 0.0
    count = 0
    for i in range(n):
        for j in range(i + 1, n):
            total += float(np.dot(vectors[i], vectors[j]))
            count += 1
    mean_sim = total / count if count else 0.0
    return mean_sim >= MIN_INTERNAL_SIM_CONSISTENCY, mean_sim


# --- API ---

@app.get("/enroll/hints")
async def enroll_hints():
    """Return suggested capture prompts (center only)."""
    return {
        "min_frames": MIN_REQUIRED_VALID_FRAMES,
        "max_frames": CAPTURE_COUNT_CENTER,
        "suggested_captures": ["Look at camera (center)"],
        "timeout_seconds": ENROLL_TIMEOUT_SEC,
    }


@app.get("/enroll/movements")
async def get_movements():
    """Return the required movement list (1 step: center)."""
    return {
        "steps": MOVEMENT_STEPS,
        "count": len(MOVEMENT_STEPS),
        "labels": ["Look straight at the camera"],
    }


@app.post("/enroll/frame")
async def enroll_frame(
    image: UploadFile = File(...),
    session_id: str = Form(...),
    employee_id: str = Form(...),
    guide_x_min: Optional[float] = Form(None),
    guide_y_min: Optional[float] = Form(None),
    guide_x_max: Optional[float] = Form(None),
    guide_y_max: Optional[float] = Form(None),
):
    """
    STEP 2 — Continuous face monitoring. For each frame: face detection + quality,
    then movement state machine. Returns movement_state, valid_frames, UI message,
    and embedding when frame is accepted. Session state stored server-side.
    """
    content = await image.read()
    img = read_image(content)
    if img is None:
        return JSONResponse({"ok": False, "reason": "Invalid image"}, status_code=400)

    guide_box = None
    if all(x is not None for x in (guide_x_min, guide_y_min, guide_x_max, guide_y_max)):
        guide_box = (float(guide_x_min), float(guide_y_min), float(guide_x_max), float(guide_y_max))

    emb, metrics, error, failure_code = validate_frame_basic(img, guide_box=guide_box, skip_pose_strict=True)
    if error:
        state = _enroll_sessions.get(session_id, {})
        guidance = _guidance_for_failure_code(failure_code, capturing=False)
        return {
            "ok": True,
            "accepted": False,
            "reason": error,
            "failure_code": failure_code or "unknown",
            "guidance": guidance,
            "movement_state": state.get("movement_state", CENTER_REQUIRED),
            "valid_frames": state.get("valid_frames", 0),
            "message": guidance,
        }

    yaw = metrics.get("yaw", 0.0)
    pitch = metrics.get("pitch", 0.0)
    roll = metrics.get("roll", 0.0)

    if session_id not in _enroll_sessions:
        _enroll_sessions[session_id] = {
            "movement_state": CENTER_REQUIRED,
            "movement_completed": 0,
            "valid_frames": 0,
            "pose_history": [],
            "captured_embeddings": [],
            "capture_remaining": 0,
            "stable_count": 0,
            "started_at": time.time(),
        }

    sess = _enroll_sessions[session_id]
    # Timeout: 20 seconds
    if time.time() - sess.get("started_at", 0) > ENROLL_TIMEOUT_SEC:
        del _enroll_sessions[session_id]
        return {
            "ok": True,
            "accepted": False,
            "reason": "Session timed out. Please try again.",
            "failure_code": "timeout",
            "guidance": "Timeout, please try again.",
            "movement_state": CENTER_REQUIRED,
            "valid_frames": 0,
            "message": "Timeout, please try again.",
            "session_completed": False,
            "timeout": True,
        }

    sess["pose_history"] = (sess["pose_history"] + [{"yaw": yaw, "pitch": pitch, "roll": roll}])[-20:]

    state = sess["movement_state"]
    state_advanced = False
    session_completed = False
    accepted = False
    embedding_out = None

    if sess["capture_remaining"] > 0:
        sess["captured_embeddings"].append(emb)
        sess["valid_frames"] += 1
        sess["capture_remaining"] -= 1
        accepted = True
        embedding_out = emb.tolist()
        if sess["capture_remaining"] == 0:
            sess["movement_completed"] = 1
            sess["movement_state"] = COMPLETED
            session_completed = True
            state_advanced = True
            state = COMPLETED
    else:
        satisfies = _pose_satisfies_state(state, yaw, pitch, roll)
        if satisfies:
            sess["stable_count"] = sess.get("stable_count", 0) + 1
            if sess["stable_count"] >= STABILITY_FRAMES:
                sess["capture_remaining"] = CAPTURE_COUNT_CENTER
                sess["stable_count"] = 0
                sess["captured_embeddings"].append(emb)
                sess["valid_frames"] += 1
                sess["capture_remaining"] -= 1
                accepted = True
                embedding_out = emb.tolist()
                if sess["capture_remaining"] == 0:
                    sess["movement_completed"] = 1
                    sess["movement_state"] = COMPLETED
                    session_completed = True
                    state_advanced = True
                    state = COMPLETED
                else:
                    state = sess["movement_state"]
            else:
                state = sess["movement_state"]
        else:
            sess["stable_count"] = 0

    guidance = "Hold still…" if accepted else _guidance_for_failure_code(None, capturing=False)
    if not accepted and state == CENTER_REQUIRED:
        guidance = _ui_message_for_state(state)
    return {
        "ok": True,
        "accepted": accepted,
        "reason": None if accepted else _ui_message_for_state(state),
        "failure_code": None if accepted else "face_not_centered",
        "guidance": guidance,
        "movement_state": state,
        "valid_frames": sess["valid_frames"],
        "message": guidance,
        "pose": {"yaw": yaw, "pitch": pitch, "roll": roll},
        "stable_count": sess.get("stable_count", 0),
        "state_advanced": state_advanced,
        "embedding": embedding_out,
        "session_completed": session_completed,
    }


@app.post("/enroll/finalize")
async def enroll_finalize(
    session_id: str = Form(...),
    employee_id: str = Form(...),
):
    """
    STEP 5 — Final enrollment decision. Requires movement_state == COMPLETED,
    valid_frames >= MIN_REQUIRED, internal embedding consistency > 0.6.
    Returns template_embedding or error (movement incomplete, low quality, liveness failed).
    """
    if session_id not in _enroll_sessions:
        return JSONResponse(
            {"ok": False, "error": "Session not found or expired.", "failure_reason": "SESSION_EXPIRED"},
            status_code=400,
        )
    sess = _enroll_sessions[session_id]
    if time.time() - sess.get("started_at", 0) > ENROLL_TIMEOUT_SEC:
        del _enroll_sessions[session_id]
        return JSONResponse(
            {"ok": False, "error": "Session timed out.", "failure_reason": "SESSION_EXPIRED"},
            status_code=400,
        )
    if sess["movement_state"] != COMPLETED:
        return JSONResponse(
            {"ok": False, "error": "Movement incomplete.", "failure_reason": "MOVEMENT_INCOMPLETE"},
            status_code=400,
        )
    if sess.get("movement_completed", 0) != 1:
        return JSONResponse(
            {"ok": False, "error": "Center step required.", "failure_reason": "MOVEMENT_INCOMPLETE"},
            status_code=400,
        )
    embeddings = sess.get("captured_embeddings", [])
    if len(embeddings) < MIN_REQUIRED_VALID_FRAMES:
        return JSONResponse(
            {"ok": False, "error": f"Too few valid frames (need {MIN_REQUIRED_VALID_FRAMES}).", "failure_reason": "LOW_QUALITY"},
            status_code=400,
        )
    consistency_ok, mean_sim = _internal_consistency_pass(embeddings)
    if not consistency_ok:
        return JSONResponse(
            {"ok": False, "error": "Face unstable or poor quality. Re-enroll with clearer movements.", "failure_reason": "LIVENESS_FAIL"},
            status_code=400,
        )
    template, sims = build_template(embeddings)
    if template is None:
        return JSONResponse(
            {"ok": False, "error": "Internal consistency failed.", "failure_reason": "LOW_QUALITY"},
            status_code=400,
        )
    quality_score = 100.0 * (mean_sim or 0.5)
    # Clear session so it can't be reused
    del _enroll_sessions[session_id]
    return {
        "ok": True,
        "employee_id": employee_id,
        "session_id": session_id,
        "model": MODEL_NAME,
        "model_version": MODEL_VERSION,
        "template_embedding": template.tolist(),
        "embedding": template.tolist(),
        "quality": {"mean_similarity": mean_sim},
        "enrollment_quality_score": quality_score,
        "valid_frames": len(embeddings),
    }


@app.post("/enroll/validate-frame")
async def validate_enroll_frame(image: UploadFile = File(...)):
    """
    Per-frame validation: face detection, quality, pose, occlusion.
    Use before adding a frame to the enrollment set. Does not extract or return embedding.
    """
    content = await image.read()
    img = read_image(content)
    if img is None:
        return JSONResponse({"ok": False, "accepted": False, "reason": "Invalid image"}, status_code=400)
    _, metrics, error = validate_frame(img)
    if error:
        return {"ok": True, "accepted": False, "reason": error}
    return {"ok": True, "accepted": True, "metrics": metrics}


@app.post("/enroll/validate-step")
async def validate_step(
    image: UploadFile = File(...),
    current_step: int = Form(1),
    employee_id: Optional[str] = Form(None),
    session_id: Optional[str] = Form(None),
    guide_x_min: Optional[float] = Form(None),
    guide_y_min: Optional[float] = Form(None),
    guide_x_max: Optional[float] = Form(None),
    guide_y_max: Optional[float] = Form(None),
):
    """
    Continuous frame processing: basic face validation then movement check for current_step.
    Returns accepted, reason, next_step (1–6 when done), and embedding if accepted.
    No skipping, no reordering; exactly 5 steps in order.
    """
    if current_step < 1 or current_step > len(MOVEMENT_STEPS):
        return JSONResponse(
            {"ok": False, "accepted": False, "reason": "Invalid step", "next_step": current_step},
            status_code=400,
        )
    content = await image.read()
    img = read_image(content)
    if img is None:
        return JSONResponse(
            {"ok": True, "accepted": False, "reason": "Invalid image", "next_step": current_step},
            status_code=200,
        )
    guide_box = None
    if guide_x_min is not None and guide_y_min is not None and guide_x_max is not None and guide_y_max is not None:
        guide_box = (float(guide_x_min), float(guide_y_min), float(guide_x_max), float(guide_y_max))
    emb, metrics, error, _ = validate_frame_basic(img, guide_box=guide_box, skip_pose_strict=True)
    if error:
        return {
            "ok": True,
            "accepted": False,
            "reason": error,
            "next_step": current_step,
        }
    yaw = metrics.get("yaw", 0.0)
    pitch = metrics.get("pitch", 0.0)
    roll = metrics.get("roll", 0.0)
    satisfied, movement_reason = check_movement_for_step(current_step, yaw, pitch, roll)
    if not satisfied:
        return {
            "ok": True,
            "accepted": False,
            "reason": movement_reason or "Complete the movement",
            "next_step": current_step,
        }
    next_step = current_step + 1
    return {
        "ok": True,
        "accepted": True,
        "reason": None,
        "next_step": min(next_step, 6),
        "current_step": current_step,
        "movement": MOVEMENT_STEPS[current_step - 1],
        "embedding": emb.tolist(),
        "metrics": {k: v for k, v in metrics.items() if k != "center"},
    }


@app.post("/enroll/complete")
async def enroll_complete(
    employee_id: str = Form(...),
    session_id: Optional[str] = Form(None),
    embeddings: str = Form(...),  # JSON array of 5 embedding vectors
):
    """
    Build template from exactly 5 embeddings (from the 5 movement steps). No reordering.
    """
    try:
        emb_list = ast.literal_eval(embeddings)
    except Exception:
        return JSONResponse(
            {"ok": False, "error": "Invalid embeddings format"},
            status_code=400,
        )
    if not isinstance(emb_list, list) or len(emb_list) != len(MOVEMENT_STEPS):
        return JSONResponse(
            {"ok": False, "error": f"Exactly {len(MOVEMENT_STEPS)} embeddings required"},
            status_code=400,
        )
    vectors = [np.array(e, dtype=np.float32) for e in emb_list]
    for v in vectors:
        v /= np.linalg.norm(v)
    template, sims = build_template(vectors)
    if template is None:
        return JSONResponse(
            {"ok": False, "error": "Captures are inconsistent. Re-enroll with clearer movements."},
            status_code=400,
        )
    mean_sim = float(np.mean(sims)) if sims else None
    quality_score = 100.0 * (mean_sim or 0.5)
    return {
        "ok": True,
        "employee_id": employee_id,
        "session_id": session_id,
        "model": MODEL_NAME,
        "model_version": MODEL_VERSION,
        "template_embedding": template.tolist(),
        "embedding": template.tolist(),
        "quality": {"mean_similarity": mean_sim},
        "enrollment_quality_score": quality_score,
    }


@app.post("/enroll")
async def enroll_face(
    employee_id: str = Form(...),
    session_id: Optional[str] = Form(None),
    device_id: Optional[str] = Form(None),
    images: List[UploadFile] = File(None),
    image: Optional[UploadFile] = File(None),
):
    """
    ArcFace enrollment: accept 5–10 images, run per-frame validation (1 face, quality, pose,
    liveness via motion), align to 112x112, extract embeddings, build template (outlier-resistant
    mean + L2), return template_embedding and model_version. Store template and model_version
    on the caller (Laravel); attendance remains locked until enrollment status = SUCCESS.
    """
    files = []
    if images:
        files.extend(images)
    if image:
        files.append(image)

    if not files:
        return JSONResponse({"ok": False, "error": "No images provided"}, status_code=400)

    legacy_single = len(files) == 1 and session_id is None  # admin/legacy flow compatibility
    min_required = 1 if legacy_single else MIN_FRAMES

    if len(files) < min_required or len(files) > MAX_FRAMES:
        return JSONResponse(
            {"ok": False, "error": f"Provide {min_required}–{MAX_FRAMES} frame(s)"},
            status_code=400
        )

    accepted_embeddings = []
    accepted_metrics = []
    frame_results = []

    for idx, upload in enumerate(files):
        content = await upload.read()
        img = read_image(content)
        frame_name = upload.filename or f"frame_{idx}"
        if img is None:
            frame_results.append({"name": frame_name, "accepted": False, "reason": "Invalid image"})
            continue

        # Legacy single-image (e.g. uploaded photo): relax pose so slight head tilt is OK
        if legacy_single:
            emb, metrics, error, _ = validate_frame_basic(img, guide_box=None, skip_pose_strict=True)
        else:
            emb, metrics, error = validate_frame(img)
        if error:
            frame_results.append({"name": frame_name, "accepted": False, "reason": error})
            continue

        accepted_embeddings.append(emb)
        accepted_metrics.append(metrics)
        frame_results.append({"name": frame_name, "accepted": True, "metrics": metrics})

    if len(accepted_embeddings) < min_required:
        return JSONResponse({
            "ok": False,
            "error": f"Need at least {min_required} good frame(s); got {len(accepted_embeddings)}",
            "frames": frame_results,
            "session_id": session_id or str(uuid.uuid4()),
        }, status_code=400)

    if legacy_single:
        live_motion = 0.0
        liveness_passed = True
        template = accepted_embeddings[0]
        sims = [1.0]
        quality_score = accepted_metrics[0]["sharpness"]
    else:
        live_motion = movement_score(accepted_metrics)
        liveness_passed = live_motion >= MIN_LIVENESS_MOVEMENT
        if not liveness_passed:
            return JSONResponse({
                "ok": False,
                "error": "Liveness failed: move your head or blink between captures",
                "frames": frame_results,
                "session_id": session_id or str(uuid.uuid4()),
            }, status_code=400)

        template, sims = build_template(accepted_embeddings)
        if template is None:
            return JSONResponse({
                "ok": False,
                "error": "Captures are inconsistent. Re-capture with better quality.",
                "frames": frame_results,
                "session_id": session_id or str(uuid.uuid4()),
            }, status_code=400)

        quality_score = float(np.mean([m["sharpness"] for m in accepted_metrics]))

    template_list = template.tolist()
    mean_sim = float(np.mean(sims)) if sims else None
    enrollment_quality_score = quality_score if mean_sim is None else (quality_score * 0.5 + mean_sim * 100.0)

    return {
        "ok": True,
        "employee_id": employee_id,
        "session_id": session_id,
        "model": MODEL_NAME,
        "model_version": MODEL_VERSION,
        "frames": frame_results,
        "frames_accepted": len(accepted_embeddings),
        "frames_rejected": len(files) - len(accepted_embeddings),
        "embeddings": [emb.tolist() for emb in accepted_embeddings],
        "embedding": template_list,           # backward-compat for admin flow
        "template_embedding": template_list,
        "quality": {
            "mean_sharpness": quality_score,
            "mean_similarity": mean_sim,
        },
        "enrollment_quality_score": enrollment_quality_score,
        "liveness": {
            "movement_score": live_motion,
            "passed": liveness_passed,
        },
        "suggested_captures": [
            "center",
            "slight left",
            "slight right",
            "slight up/down",
            "neutral or smile",
        ] + (["more variety"] * (MAX_FRAMES - 5)),
    }


def _error_to_failure_reason(error: Optional[str]) -> str:
    """Map validation error message to attendance failure_reason code."""
    if not error:
        return "LOW_QUALITY"
    if "No face" in error or "no face" in error.lower():
        return "NO_FACE"
    if "Only one person" in error or "one person" in error.lower():
        return "MULTI_FACE"
    if "Liveness" in error or "liveness" in error.lower():
        return "LIVENESS_FAIL"
    return "LOW_QUALITY"


@app.post("/attend")
async def attend_face(
    user_id: str = Form(...),
    stored_embedding: str = Form(...),
    threshold: float = Form(0.35),
    images: List[UploadFile] = File(None),
    image: Optional[UploadFile] = File(None),
):
    """
    Attendance scan: 1–3 frames, validate each, use best match vs stored template.
    Returns failure_reason code for policy/feedback: NO_FACE, MULTI_FACE, LOW_QUALITY, LIVENESS_FAIL, BELOW_THRESHOLD.
    """
    files = []
    if images:
        files.extend(images)
    if image:
        files.append(image)
    if not files:
        return JSONResponse({
            "ok": False,
            "error": "No image provided",
            "failure_reason": "LOW_QUALITY",
        }, status_code=400)

    # Limit to 3 frames for speed
    files = files[:3]
    last_error = None
    last_reason = "LOW_QUALITY"
    best_emb = None
    best_score = -1.0

    try:
        emb_list = ast.literal_eval(stored_embedding)
        db_emb = np.array(emb_list, dtype=np.float32)
        db_emb = db_emb / np.linalg.norm(db_emb)
    except Exception:
        return JSONResponse({
            "ok": False,
            "error": "Invalid stored embedding format",
            "failure_reason": "LOW_QUALITY",
        }, status_code=400)

    for upload in files:
        content = await upload.read()
        img = read_image(content)
        if img is None:
            last_error = "Invalid image"
            last_reason = "LOW_QUALITY"
            continue
        emb, _, error, _ = validate_frame_relaxed_pose(img)
        if error:
            last_error = error
            last_reason = _error_to_failure_reason(error)
            continue
        score = float(cosine_similarity(emb, db_emb))
        if score > best_score:
            best_score = score
            best_emb = emb

    if best_emb is None:
        return JSONResponse({
            "ok": False,
            "error": last_error or "No valid frame",
            "failure_reason": last_reason,
        }, status_code=400)

    matched = best_score >= threshold
    if not matched:
        return JSONResponse({
            "ok": True,
            "user_id": user_id,
            "score": best_score,
            "threshold": threshold,
            "matched": False,
            "failure_reason": "BELOW_THRESHOLD",
        }, status_code=200)

    return {
        "ok": True,
        "user_id": user_id,
        "score": best_score,
        "threshold": threshold,
        "matched": True,
        "failure_reason": None,
    }


@app.post("/verify")
@app.post("/match")
async def verify_face(
    user_id: str = Form(...),
    stored_embedding: str = Form(...),  # JSON string or Python list string
    threshold: float = Form(0.35),
    images: List[UploadFile] = File(None),
    image: Optional[UploadFile] = File(None),
):
    """
    Verification scan: accept 1–3 frames and use the best match vs stored template.
    Backward compatible with single `image` field.
    """
    files: List[UploadFile] = []
    if images:
        files.extend(images)
    if image:
        files.append(image)
    if not files:
        return JSONResponse({"ok": False, "error": "No image provided"}, status_code=400)

    files = files[:3]

    # safer than eval()
    try:
        emb_list = ast.literal_eval(stored_embedding)  # expects "[...]" list
        db_emb = np.array(emb_list, dtype=np.float32)
        db_emb = db_emb / np.linalg.norm(db_emb)
    except Exception:
        return JSONResponse({"ok": False, "error": "Invalid stored embedding format"}, status_code=400)

    last_error = None
    best_score = -1.0

    for upload in files:
        img = read_image(await upload.read())
        if img is None:
            last_error = "Invalid image"
            continue

        live_emb, _, error, _ = validate_frame_relaxed_pose(img)
        if live_emb is None:
            last_error = error or "No face detected"
            continue

        score = cosine_similarity(live_emb, db_emb)
        if score > best_score:
            best_score = score

    if best_score < 0:
        err = last_error or "No valid frame"
        return JSONResponse({
            "ok": False,
            "error": err,
            "failure_reason": _error_to_failure_reason(err),
        }, status_code=400)

    matched = best_score >= threshold

    return {
        "ok": True,
        "user_id": user_id,
        "score": best_score,
        "threshold": threshold,
        "matched": matched
    }
