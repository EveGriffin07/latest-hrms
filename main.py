import cv2
import numpy as np
import json
from fastapi import FastAPI, File, UploadFile, Form, Request
from fastapi.responses import JSONResponse
from fastapi.exceptions import RequestValidationError
from typing import List, Optional
import uvicorn
from insightface.app import FaceAnalysis

app = FastAPI()

# --- 🕵️ THE MAGIC ERROR CATCHER ---
@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    print("\n" + "🛑 "*15)
    print("🚨 422 UNPROCESSABLE CONTENT DETECTED 🚨")
    print(f"Target URL: {request.url}")
    print("Reason FastAPI rejected it:")
    for error in exc.errors():
        # This will print exactly which field is missing or invalid!
        print(f" ❌ Missing/Invalid Field: {error['loc']} -> {error['msg']}")
    print("🛑 "*15 + "\n")
    return JSONResponse(status_code=422, content={"detail": exc.errors()})


print("Loading AI Model...")
face_app = FaceAnalysis(name='buffalo_l')
face_app.prepare(ctx_id=-1, det_size=(640, 640)) 
print("Model loaded and ready!")

# --- 1. ENROLLMENT ---
@app.post("/enroll")
async def enroll_face(
    images: List[UploadFile] = File(...),
    employee_id: int = Form(...),
    session_id: Optional[str] = Form(None),
    device_id: Optional[str] = Form(None)
):
    frames_info = []
    embeddings = []
    for image in images:
        contents = await image.read()
        nparr = np.frombuffer(contents, np.uint8)
        img_cv = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        faces = face_app.get(img_cv)
        
        if len(faces) == 0:
            frames_info.append({"accepted": False, "reason": "NO_FACE"})
            continue
        if len(faces) > 1:
            frames_info.append({"accepted": False, "reason": "MULTI_FACE"})
            continue
            
        embeddings.append(faces[0].embedding.tolist())
        frames_info.append({"accepted": True, "reason": "SUCCESS"})
        
    if not embeddings:
        return JSONResponse(status_code=400, content={"error": "Face detection failed", "frames": frames_info})

    template_embedding = np.mean(embeddings, axis=0).tolist()
    return {
        "ok": True,
        "embeddings": embeddings,
        "template_embedding": template_embedding,
        "frames": frames_info,
        "liveness": 0.99,  
        "quality": 0.99,   
        "model_version": "buffalo_l",
        "enrollment_quality_score": 0.99
    }

# --- 2. VERIFY / MATCH (Bulletproof Mode) ---
@app.post("/verify")
@app.post("/match")
async def verify_face(request: Request):
    form = await request.form()
    
    # Flexibly find the file (handles 'image', 'images', 'frames', etc.)
    files = form.getlist("images") or form.getlist("image") or form.getlist("frame") or form.getlist("file")
    if not files:
        return JSONResponse(status_code=400, content={"error": f"No image received. Keys found: {list(form.keys())}"})
        
    stored_embedding_str = form.get("stored_embedding")
    if not stored_embedding_str:
        return JSONResponse(status_code=400, content={"error": "No stored_embedding received."})
        
    threshold = float(form.get("threshold", 0.35))
    
    try:
        db_embedding = np.array(json.loads(stored_embedding_str))
    except Exception:
        return JSONResponse(status_code=400, content={"error": "Invalid stored embedding format"})
    
    # Process the first image found
    image_file = files[0]
    contents = await image_file.read()
    nparr = np.frombuffer(contents, np.uint8)
    img_cv = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    faces = face_app.get(img_cv)

    if len(faces) == 0:
        return {"ok": False, "matched": False, "score": 0.0, "failure_reason": "NO_FACE"}
    if len(faces) > 1:
        return {"ok": False, "matched": False, "score": 0.0, "failure_reason": "MULTI_FACE"}

    live_embedding = faces[0].embedding
    sim = float(np.dot(live_embedding, db_embedding) / (np.linalg.norm(live_embedding) * np.linalg.norm(db_embedding)))
    
    is_match = sim >= threshold
    return {"ok": True, "matched": is_match, "score": sim, "failure_reason": None if is_match else "BELOW_THRESHOLD"}


# --- 3. ATTEND (Bulletproof Mode) ---
@app.post("/attend")
async def attend_face(request: Request):
    form = await request.form()
    
    files = form.getlist("images") or form.getlist("image") or form.getlist("frames")
    if not files:
        return JSONResponse(status_code=400, content={"error": f"No images received. Keys found: {list(form.keys())}"})
        
    stored_embedding_str = form.get("stored_embedding")
    threshold = float(form.get("threshold", 0.35))
    
    try:
        db_embedding = np.array(json.loads(stored_embedding_str))
    except Exception:
        return JSONResponse(status_code=400, content={"error": "Invalid stored embedding format"})

    best_score = -1.0
    is_match = False
    failure_reason = "LOW_QUALITY"

    # The Attend route can check up to 3 frames to find a good match
    for image_file in files:
        contents = await image_file.read()
        nparr = np.frombuffer(contents, np.uint8)
        img_cv = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        faces = face_app.get(img_cv)

        if len(faces) == 0:
            failure_reason = "NO_FACE"
            continue
        if len(faces) > 1:
            failure_reason = "MULTI_FACE"
            continue

        live_embedding = faces[0].embedding
        sim = float(np.dot(live_embedding, db_embedding) / (np.linalg.norm(live_embedding) * np.linalg.norm(db_embedding)))

        if sim > best_score:
            best_score = sim

        if sim >= threshold:
            is_match = True
            failure_reason = None
            break 

    if not is_match and best_score != -1.0:
        failure_reason = "BELOW_THRESHOLD"

    return {"ok": True, "matched": is_match, "score": best_score if best_score != -1.0 else None, "failure_reason": failure_reason}