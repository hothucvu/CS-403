# Nhóm 2 – Sprint 1, Tuần 2 & 3 (AI Microservice + Auto-Tagging)

## Phạm vi công việc
| Tuần | Công việc | File |
|---|---|---|
| 2 | Dựng khung Python Flask Microservice, thử nghiệm Gemini nhận diện ảnh thô | `AI/app.py`, `AI/gemini_service.py` (`/health`, `/api/vision-test`) |
| 3 | Hoàn thiện Auto-Tagging (ảnh → danh mục + tag) | `/api/auto-tag`, `BE/ai-autotag.php`, `FE/assets/js/auto-tag.js` |

## Cách bỏ vào code chính (giữ nguyên cấu trúc thư mục)
1. Chép đè/thêm: `AI/*` (thay `AI/app.py` cũ), `BE/ai-autotag.php`, `FE/assets/js/auto-tag.js`.
2. Chạy `AI/seed_categories.sql` trong MySQL (bảng `categories` đang trống → AI không có danh mục để chọn).
3. Cài và chạy AI service:
   ```
   cd AI
   pip install -r requirements.txt
   copy .env.example .env      # điền GEMINI_API_KEY (lấy tại aistudio.google.com)
   python app.py
   ```
   Chưa có key? Đặt `AI_MOCK=1` trong `.env` để Nhóm 3, 4 test với dữ liệu giả.
4. Thêm `.env` vào `.gitignore`.
5. Bật extension `curl` và `fileinfo` trong `php.ini` (XAMPP thường đã bật).

## Nhóm 4 gắn vào form đăng bán (`pages/create-product.php`)
```html
<input type="file" id="product-image" name="image" accept="image/*">
<select id="category" name="category_id"> <!-- đổ từ bảng categories --> </select>
<input type="text" id="title" name="title">
<input type="hidden" id="ai-tags" name="ai_tags">   <!-- lưu vào products.ai_tags -->
<p id="ai-status"></p>

<script src="./assets/js/auto-tag.js"></script>
<script>
  initAutoTag({
    fileInput: '#product-image', categorySelect: '#category',
    titleInput: '#title', tagsInput: '#ai-tags', statusEl: '#ai-status',
    endpoint: '../BE/ai-autotag.php'   // chỉnh theo đường dẫn thực tế
  });
</script>
```

## Định dạng JSON `/api/auto-tag` (qua `BE/ai-autotag.php`)
```json
{ "status": "success", "title": "Laptop Lenovo cũ",
  "category": {"id": 1, "name": "Đồ điện tử"},
  "suggested_tags": ["Laptop cũ", "Lenovo", "Màu bạc"],
  "brand": "Lenovo", "condition_hint": "...", "confidence": 0.9 }
```
Lỗi: `{"status":"error","error":"..."}` kèm mã 400/413/415/502/503.

## Lưu ý phát hiện khi đọc code chung (báo nhóm phụ trách)
- `csdl.sql`, bảng `barter_requests`: `offered_product_id INT INT NOT NULL` (và `requested_product_id`) bị viết thừa `INT` → lỗi cú pháp khi import.
- `FE/layout.php`: route `product-detail` trỏ `pages/product-detail.php`, nhưng file thật là `pages/productdetail.php`.
- `pages/create-product.php` được khai báo route nhưng chưa có file (Nhóm 4 làm).
