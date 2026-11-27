"""
AI/gemini_service.py  -  Nhóm 2
Gói toàn bộ phần gọi Gemini để app.py chỉ lo routing.

- describe_image(): nhận diện ảnh "thô" (Sprint 1 - Tuần 2)
- auto_tag():       nhận diện ảnh đồ cũ -> danh mục + tag + tiêu đề gợi ý (Sprint 1 - Tuần 3)
"""
import json
import os
import re
import time
import hashlib
from pathlib import Path
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:  # chưa cài python-dotenv thì đọc biến môi trường hệ thống
    pass

GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
MAX_TAGS = 8

_client = None
def is_mock() -> bool:
#     """Chạy giả lập khi AI_MOCK=1 hoặc chưa có API key."""
#     return os.getenv("AI_MOCK", "0") == "1" or not os.getenv("GEMINI_API_KEY")
    key = os.getenv("GEMINI_API_KEY", "").strip()
    return os.getenv("AI_MOCK", "0") == "1" or key in ("", "your_api_key_here")

def _get_client():
    global _client
    if _client is None:
        from google import genai
        _client = genai.Client(api_key=os.getenv("GEMINI_API_KEY"))
    return _client

# def _call_gemini(prompt: str, image_bytes: bytes, mime_type: str, as_json: bool) -> str:
#     from google.genai import types

#     config = types.GenerateContentConfig(
#         temperature=0.2,
#         response_mime_type="application/json" if as_json else "text/plain",
#     )
#     response = _get_client().models.generate_content(
#         model=GEMINI_MODEL,
#         contents=[types.Part.from_bytes(data=image_bytes, mime_type=mime_type), prompt],
#         config=config,
#     )
#     return (response.text or "").strip()
THINKING_LEVEL = os.getenv("GEMINI_THINKING_LEVEL", "low")  # để trống để tắt
CACHE_ENABLED = os.getenv("GEMINI_CACHE", "1") == "1"
CACHE_DIR = Path(__file__).resolve().parent / ".cache"


def _cache_key(prompt: str, image_bytes: bytes, mime_type: str, as_json: bool) -> str:
    """Khóa cache: đổi ảnh, model, thinking, prompt (kể cả danh mục) là ra khóa mới."""
    h = hashlib.sha256()
    for part in (GEMINI_MODEL, THINKING_LEVEL, str(as_json), mime_type, prompt):
        h.update(part.encode("utf-8"))
        h.update(b"\0")
    h.update(image_bytes)
    return h.hexdigest()


def _cache_get(key: str):
    if not CACHE_ENABLED:
        return None
    try:
        f = CACHE_DIR / f"{key}.txt"
        return f.read_text(encoding="utf-8") if f.exists() else None
    except OSError:
        return None


def _cache_set(key: str, text: str) -> None:
    if not CACHE_ENABLED or not text:
        return
    try:
        CACHE_DIR.mkdir(exist_ok=True)
        (CACHE_DIR / f"{key}.txt").write_text(text, encoding="utf-8")
    except OSError:
        pass  # lỗi ghi cache thì bỏ qua, không làm hỏng request

def _make_config(types, as_json: bool, with_thinking: bool):
    kwargs = {
        "temperature": 0.2,
        "response_mime_type": "application/json" if as_json else "text/plain",
    }
    if with_thinking and THINKING_LEVEL:
        kwargs["thinking_config"] = types.ThinkingConfig(thinking_level=THINKING_LEVEL)
    return types.GenerateContentConfig(**kwargs)


def _call_gemini(prompt: str, image_bytes: bytes, mime_type: str, as_json: bool) -> str:
    from google.genai import types

    key = _cache_key(prompt, image_bytes, mime_type, as_json)
    cached = _cache_get(key)
    if cached is not None:
        print("[cache] HIT - không gọi Gemini")
        return cached

    use_thinking = True
    last_exc = None
    for attempt in range(3):
        try:
            config = _make_config(types, as_json, use_thinking)
            response = _get_client().models.generate_content(
                model=GEMINI_MODEL,
                contents=[types.Part.from_bytes(data=image_bytes, mime_type=mime_type), prompt],
                config=config,
            )
            text = (response.text or "").strip()
            if as_json:
                _clean_json(text)  # JSON hỏng thì ném lỗi ở đây và KHÔNG được cache
            _cache_set(key, text)
            return text
        except Exception as exc:
            last_exc = exc
            msg = str(exc)
            if use_thinking and "thinking" in msg.lower():
                use_thinking = False      # tham số thinking không được hỗ trợ -> bỏ đi
                continue
            if "503" in msg or "UNAVAILABLE" in msg:
                time.sleep(2 ** attempt)  # model quá tải -> chờ 1s, 2s, 4s rồi thử lại
                continue
            raise
    raise last_exc


# ---------------------------------------------------------------------------
# Tuần 2: nhận diện hình ảnh thô
# ---------------------------------------------------------------------------
def describe_image(image_bytes: bytes, mime_type: str) -> dict:
    if is_mock():
        return {"description": "[MOCK] Một chiếc laptop cũ màu bạc đặt trên bàn gỗ.", "mock": True}

    prompt = (
        "Hãy mô tả ngắn gọn bằng tiếng Việt (2-3 câu) vật thể chính trong ảnh: "
        "đó là món đồ gì, màu sắc, tình trạng bên ngoài (mới/cũ/trầy xước...)."
    )
    return {"description": _call_gemini(prompt, image_bytes, mime_type, as_json=False), "mock": False}


# ---------------------------------------------------------------------------
# Tuần 3: Auto-Tagging
# ---------------------------------------------------------------------------
def _build_prompt(categories: list) -> str:
    if categories:
        cat_lines = "\n".join(f'- id={c["id"]}: {c["name"]}' for c in categories)
        cat_rule = (
            "Chọn ĐÚNG 1 danh mục phù hợp nhất trong danh sách sau, trả về category_id "
            "(nếu không món nào phù hợp thì category_id = null):\n" + cat_lines
        )
    else:
        cat_rule = "Đề xuất tên danh mục phù hợp nhất ở trường category_name, category_id = null."

    return f"""Bạn là trợ lý của chợ đồ cũ. Hãy phân tích ảnh sản phẩm đồ cũ và trả về JSON hợp lệ,
không kèm giải thích hay markdown, theo đúng cấu trúc:
{{
  "title": "tiêu đề đăng bán gợi ý, ngắn gọn, tiếng Việt",
  "category_id": số hoặc null,
  "category_name": "tên danh mục",
  "tags": ["tối đa {MAX_TAGS} thẻ tag ngắn, tiếng Việt, gồm loại đồ, thương hiệu nếu thấy rõ, màu sắc, chất liệu"],
  "brand": "thương hiệu nếu nhìn thấy, không thì chuỗi rỗng",
  "condition_hint": "nhận xét ngắn về tình trạng thấy được trong ảnh",
  "confidence": số từ 0 đến 1
}}
{cat_rule}
Nếu ảnh không phải là một món đồ có thể bán, đặt "confidence" = 0 và "tags" = []."""


def _clean_json(text: str) -> dict:
    text = re.sub(r"^```(?:json)?|```$", "", text.strip(), flags=re.MULTILINE).strip()
    return json.loads(text)


def _normalize_tags(tags) -> list:
    seen, result = set(), []
    for t in tags or []:
        t = str(t).strip()
        if t and t.lower() not in seen:
            seen.add(t.lower())
            result.append(t)
    return result[:MAX_TAGS]


def _resolve_category(data: dict, categories: list):
    """Đối chiếu category AI trả về với danh mục thật trong DB."""
    if not categories:
        return {"id": None, "name": data.get("category_name") or ""}

    by_id = {int(c["id"]): c for c in categories}
    try:
        cid = int(data.get("category_id"))
        if cid in by_id:
            return {"id": cid, "name": by_id[cid]["name"]}
    except (TypeError, ValueError):
        pass

    # Dự phòng: khớp theo tên
    name = (data.get("category_name") or "").strip().lower()
    for c in categories:
        if name and (name == c["name"].lower() or name in c["name"].lower()):
            return {"id": int(c["id"]), "name": c["name"]}
    return {"id": None, "name": data.get("category_name") or ""}


def auto_tag(image_bytes: bytes, mime_type: str, categories: list | None = None) -> dict:
    categories = categories or []

    if is_mock():
        first = categories[0] if categories else None
        return {
            "title": "Laptop Lenovo cũ",
            "category": {"id": int(first["id"]), "name": first["name"]} if first
                        else {"id": None, "name": "Đồ điện tử"},
            "tags": ["Đồ điện tử", "Laptop cũ", "Lenovo"],
            "brand": "Lenovo",
            "condition_hint": "[MOCK] Máy còn khá mới, vỏ ít trầy xước.",
            "confidence": 0.9,
            "mock": True,
        }

    data = _clean_json(_call_gemini(_build_prompt(categories), image_bytes, mime_type, as_json=True))
    try:
        confidence = max(0.0, min(1.0, float(data.get("confidence", 0))))
    except (TypeError, ValueError):
        confidence = 0.0

    return {
        "title": str(data.get("title") or "").strip(),
        "category": _resolve_category(data, categories),
        "tags": _normalize_tags(data.get("tags")),
        "brand": str(data.get("brand") or "").strip(),
        "condition_hint": str(data.get("condition_hint") or "").strip(),
        "confidence": confidence,
        "mock": False,
    }
