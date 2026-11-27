# Báo cáo tiến độ phần Bảo – CS403 ReUse

| Mục Bảo | Kết quả trong bản nâng cấp |
|---|---|
| #3 ERD/migration/model/seeder | SQL gốc sửa cú pháp; migration bổ sung `wallet_transactions`/`barter_confirmations`; `seed_demo.php`. PHP dùng truy vấn `mysqli`, không phải Laravel Eloquent. |
| #7 API sản phẩm | GET danh sách, phân trang, tìm kiếm, lọc giá, hình thức, danh mục; GET chi tiết và GET danh mục. |
| #11 Checkout và orders | Giỏ hàng → tạo đơn trong transaction, khóa row sản phẩm, chi tiết đơn, đơn mua của tôi. |
| #15 Fix API/Postman | Postman collection cho các API JSON; kiểm tra cú pháp bằng `php -l`, checklist test thủ công. |
| #18 Barter Token | Ví điểm, admin điều chỉnh điểm demo và ledger; chưa có mua/bán điểm hay thanh toán thật. |
| #22 CRUD/đơn Admin | Admin tạo/sửa/ẩn/duyệt sản phẩm, điều chỉnh trạng thái đơn và COD giả lập. |
| #25 Matching/gợi ý giá/cache/độ trễ | Heuristic theo giá/danh mục/độ mới; cache file TTL 20–60 giây, đo `latency_ms`. **Chưa tích hợp model AI Nhóm 2 hoặc Redis.** |
| #28 Seeder/demo | Seeder danh mục + 2 sản phẩm (khi có 2 user), kịch bản test trong tài liệu này. |
| #29 Báo cáo nhóm | Bản tóm tắt này để trình bày phần Backend. |

**Cần phối hợp:** Nhóm Hưng thử checkout tích hợp cart, các màn hình Profile/Barter. Nhóm AI thống nhất API gợi ý giá, phát hiện danh mục và moderation. Nhóm giao diện test UI responsive. Nhóm DB quyết định sơ đồ chính thức và migration đồng bộ. Chưa triển khai vận chuyển, thanh toán online hoặc ví điểm giao dịch thực.
