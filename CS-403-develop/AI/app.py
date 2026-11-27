"""
AI/app.py  -  Flask Microservice (Nhóm 2)
Chạy:  python app.py   ->  http://127.0.0.1:5000

Endpoint:
  GET  /health            kiểm tra service sống
  POST /api/vision-test   [Tuần 2] nhận diện ảnh thô bằng Gemini
  POST /api/auto-tag      [Tuần 3] Auto-Tagging: danh mục + tag + tiêu đề gợi ý
"""
import json
import os

from flask import Flask, jsonify, request, send_from_directory

import gemini_service as ai

app = Flask(__name__)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
app.config["MAX_CONTENT_LENGTH"] = 6 * 1024 * 1024  # tối đa 6MB / request

ALLOWED_MIME = {"image/jpeg", "image/png", "image/webp"}
MAX_IMAGE_BYTES = 5 * 1024 * 1024


@app.after_request
def add_cors(resp):
    # Cho phép gọi thử từ trình duyệt khi dev. Khi chạy thật nên gọi qua BE/ai-autotag.php
    resp.headers["Access-Control-Allow-Origin"] = "*"
    resp.headers["Access-Control-Allow-Headers"] = "Content-Type"
    return resp


def _read_image():
    """Đọc + kiểm tra file ảnh. Trả (ảnh, None) hoặc (None, response_lỗi)."""
    if "image" not in request.files:
        return None, (jsonify({"status": "error", "error": "Không tìm thấy file ảnh (field 'image')"}), 400)

    file = request.files["image"]
    mime = (file.mimetype or "").lower()
    if mime not in ALLOWED_MIME:
        return None, (jsonify({"status": "error", "error": "Chỉ hỗ trợ ảnh JPG, PNG, WEBP"}), 415)

    data = file.read()
    if not data:
        return None, (jsonify({"status": "error", "error": "File ảnh rỗng"}), 400)
    if len(data) > MAX_IMAGE_BYTES:
        return None, (jsonify({"status": "error", "error": "Ảnh vượt quá 5MB"}), 413)
    return (data, mime), None

# def _ai_error(exc):
#     """Đổi lỗi thô của Gemini thành thông báo tiếng Việt + mã HTTP phù hợp."""
#     msg = str(exc)
#     print(f"[AI ERROR] {msg}")  # lỗi đầy đủ vẫn in ra terminal để debug

#     if "429" in msg or "RESOURCE_EXHAUSTED" in msg:
#         text, code = "AI đã hết hạn mức miễn phí hôm nay, vui lòng thử lại sau hoặc nhập tay.", 429
#     elif "503" in msg or "UNAVAILABLE" in msg:
#         text, code = "AI đang quá tải, vui lòng thử lại sau ít phút.", 503
#     elif "404" in msg or "NOT_FOUND" in msg:
#         text, code = "Model AI không tồn tại hoặc đã ngừng hỗ trợ (kiểm tra GEMINI_MODEL trong .env).", 502
#     elif "API key" in msg or "API_KEY" in msg or "PERMISSION_DENIED" in msg or "UNAUTHENTICATED" in msg:
#         text, code = "API key Gemini không hợp lệ hoặc không có quyền (kiểm tra GEMINI_API_KEY trong .env).", 502
#     elif isinstance(exc, ValueError):  # JSON AI trả về bị hỏng
#         text, code = "AI trả về dữ liệu không đọc được, vui lòng thử lại.", 502
#     else:
#         text, code = "Không nhận diện được ảnh, vui lòng thử lại.", 502

#     return jsonify({"status": "error", "error": text}), code
@app.route("/", methods=["GET"])
def demo_page():
    return send_from_directory(BASE_DIR, "demo.html")


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "model": ai.GEMINI_MODEL, "mock": ai.is_mock()})


@app.route("/api/vision-test", methods=["POST"])
def vision_test():
    img, err = _read_image()
    if err:
        return err
    try:
        result = ai.describe_image(*img)
    except Exception as exc:  # lỗi mạng / key sai / hết quota
        return jsonify({"status": "error", "error": f"Gemini lỗi: {exc}"}), 502
    return jsonify({"status": "success", **result})
    # try:
    #     result = ai.describe_image(*img)
    # except Exception as exc:
    #     return _ai_error(exc)
    # return jsonify({"status": "success", **result})


@app.route("/api/auto-tag", methods=["POST"])
def auto_tag_item():
    img, err = _read_image()
    if err:
        return err

    # Danh sách danh mục từ DB do BE (PHP) gửi kèm: JSON [{"id":1,"name":"Đồ điện tử"}, ...]
    categories = []
    raw = request.form.get("categories")
    if raw:
        try:
            categories = [c for c in json.loads(raw) if "id" in c and "name" in c]
        except (ValueError, TypeError):
            return jsonify({"status": "error", "error": "Trường 'categories' không phải JSON hợp lệ"}), 400

    try:
        result = ai.auto_tag(img[0], img[1], categories)
    except Exception as exc:
        return jsonify({"status": "error", "error": f"Không nhận diện được ảnh: {exc}"}), 502
    # try:
    #     result = ai.describe_image(*img)
    # except Exception as exc:
    #     return _ai_error(exc)
    # return jsonify({"status": "success", **result})

    return jsonify({
        "status": "success",
        "suggested_tags": result["tags"],  # giữ tên cũ để không vỡ code đã gọi thử
        **result,
    })


@app.errorhandler(413)
def too_large(_):
    return jsonify({"status": "error", "error": "Ảnh vượt quá dung lượng cho phép"}), 413


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=int(os.getenv("FLASK_PORT", 5000)), debug=True)
    # app.run(host="0.0.0.0", port=int(os.getenv("FLASK_PORT", 5000)), debug=True)
