# CS403 – Backend Bảo (MVP tích hợp ReUse)

## 1. Môi trường

- PHP 8.1+ với `mysqli`, `mbstring`, `fileinfo` và MySQL 8.x.
- Dùng **Laragon**, Apache có thể chạy tại `localhost:8080` (hoặc cổng 80).
- Mã nguồn nằm trong `C:\laragon\www\CS-403` (không chép thư mục `CS-403` lồng nhau).
- File `BE/db.php` kết nối database `cs403` (mặc định `root` / rỗng cho Laragon local).
- **Sao lưu `cs403` trước khi thực hiện thay đổi database.** Không xóa và không nhập lại bản backup chứa người dùng khi đã có dữ liệu.

## 2. Cập nhật bảng mới (GIỮ NGUYÊN dữ liệu hiện có)

- Mở HeidiSQL, kết nối MySQL cục bộ.
- Đảm bảo database `cs403` đã tồn tại và chứa các bảng cũ `users`, `products`, `orders`, `order_items`, `carts`, `barter_requests`.
- Chạy file **`database/migrations/001_bao_features.sql`** trong cửa sổ Query.
- Migration chỉ thêm `wallet_transactions` và `barter_confirmations`; có `CREATE TABLE IF NOT EXISTS`.
- Nếu tạo database mới hoàn toàn, `csdl.sql` đã sửa lỗi `INT INT`, và gồm thêm hai bảng mở rộng. Không chạy file `csdl.sql` này trên dữ liệu đang hoạt động nếu chưa sao lưu và kiểm tra.

## 3. Quyền Admin cho demo local

- Trước tiên đăng ký tài khoản trên ReUse.
- Trong Terminal Laragon tại thư mục dự án:
  `php database/grant_admin.php email-cua-ban@example.com`
- **Đăng xuất và đăng nhập lại** để session cập nhật quyền Admin.
- Script chỉ chạy qua CLI, không có tài khoản admin hoặc mật khẩu mặc định.

## 4. Dữ liệu thử nghiệm

- Chạy `php database/seed_demo.php` bằng Terminal Laragon (không chạy qua trình duyệt).
- Seeder tạo thêm danh mục nếu chưa có; khi có ít nhất 2 tài khoản, tạo 2 sản phẩm demo khác chủ, không tạo tài khoản/password/token.
- Chạy lặp lại không cố tình tạo lại các sản phẩm cùng tên và người đăng.

## 5. Đường dẫn giao diện

| Trang | URL sau `/FE/layout.php?` |
|---|---|
| Trang chủ | `page=home` |
| Đăng bán của người dùng | `page=create-product` |
| Tin đăng của tôi | `page=my-products` |
| Hồ sơ cá nhân | `page=profile` |
| Giỏ hàng | `page=cart` |
| Trao đổi | `page=barter` |
| Thanh toán COD giả lập | `page=checkout` |
| Đơn mua của tôi | `page=my-orders` |
| Chi tiết đơn của tôi | `page=order-detail&id=1` |
| Ví Barter Token | `page=wallet` |
| Admin – sản phẩm | `page=admin-products` |
| Admin – đơn hàng | `page=admin-orders` |
| Admin – điểm demo | `page=admin-wallet` |

Trên máy đặt Laragon cổng 8080, ví dụ: `http://localhost:8080/CS-403/FE/layout.php?page=checkout`.

## 6. Các API JSON public (chỉ đọc)

- `GET /CS-403/BE/api/categories.php`
- `GET /CS-403/BE/api/products.php?page=1&per_page=12&q=ban&category_id=1&type=sell&sort=price_asc&min_price=0&max_price=900000`
- `GET /CS-403/BE/api/product.php?id=1`
- `GET /CS-403/BE/api/price-suggest.php?product_id=1`
- `GET /CS-403/BE/api/barter-matches.php?product_id=3`
- `GET /CS-403/BE/api/orders.php` (cần cookie phiên đăng nhập)
- `GET /CS-403/BE/api/order-detail.php?id=1` (cần cookie phiên, chỉ xem đơn của mình)

`products` có phân trang, tìm kiếm, lọc giá/danh mục/hình thức, sắp xếp. `meta.cache_hit` và `meta.latency_ms` mô tả cache file TTL và thời gian xử lý của API. Cache là demo (20–60 giây), không phải Redis sản xuất và có thể hiển thị dữ liệu cũ trong TTL.

Gợi ý giá dùng trung vị điều chỉnh độ mới; Barter Matching dùng chênh lệch giá + danh mục. **Hai cách này là thuật toán gợi ý quy tắc, KHÔNG phải AI học máy**; muốn kết nối Nhóm AI thì cần thống nhất API/model với nhóm đó.

## 7. Quy tắc giao dịch

1. Người bán đăng tin mới / sửa tin → trạng thái kiểm duyệt `pending`; admin duyệt `approved`. Người mua đăng nhập bằng tài khoản **khác chủ sản phẩm**, thêm hàng `active`, `approved`, hình thức `sell` hoặc `both`.
2. Tới giỏ hàng → **Tiến hành đặt hàng** → nhập địa chỉ → xác nhận COD. Hệ thống dùng MySQL transaction và khóa hàng khi tạo `orders`/`order_items`, đánh dấu sản phẩm `sold`, xóa giỏ hàng.
3. Trong **Đơn mua**, chỉ người mua sở hữu đơn mới thấy chi tiết. Trạng thái mặc định `pending`, thanh toán `pending`.
4. Admin có thể cập nhật `pending → shipping → completed` hoặc hủy đơn chưa thanh toán; hủy phục hồi sản phẩm từ `sold` sang `active`.
5. Admin nút **COD đã thu (demo)** chỉ đặt `payment_status='paid'`, không chuyển tiền thật.
6. Barter: người nhận đồng ý trước, sau đó **cả người gửi và người nhận phải xác nhận đã bàn giao**. Khi cả hai xác nhận, hệ thống chuyển `status=completed`, đổi chủ hai món và đánh dấu `bartered`. Các yêu cầu khác cho món đó bị từ chối. Không áp dụng bù tiền/điểm.
7. Admin có thể cộng/trừ Barter Token nội bộ; mọi thao tác tạo dòng `wallet_transactions` và không được để số dư âm. Không có cổng nạp/rút tiền thật.

## 8. Kiểm thử an toàn (bắt buộc trước khi merge)

- Hai tài khoản khác nhau; thử tự mua đồ của chính mình → không được.
- Thử thanh toán cùng món từ hai tài khoản song song → chỉ một đơn thành công.
- Thử địa chỉ trống, CSRF không hợp lệ, mặt hàng `hidden`/`sold`/`bartered` → không được đặt.
- Người mua A mở `page=order-detail&id=<id của B>` → phải báo không có quyền.
- User thường mở `page=admin-products` và `page=admin-orders` → phải 403.
- Hủy đơn chưa thu tiền → sản phẩm về `active`. Hủy đơn đã thu tiền → bị từ chối.
- Xác nhận Barter chỉ bởi đúng hai chủ và chỉ khi yêu cầu đã `accepted`.
- Barter chỉ thành `completed` sau **hai** xác nhận; sản phẩm không còn bán/đổi được.
- Kiểm tra SQL trong `csdl.sql` đã sửa `INT INT`.

## 9. Tình trạng và giới hạn

Đã tạo mã nguồn các chức năng trên và kiểm tra cú pháp PHP, **chưa thể kiểm thử chạy trực tiếp với MySQL/Laragon của bạn**. Các tính năng vận chuyển, thanh toán online, QR, điểm thưởng dựa trên giao dịch thực, AI/model của Nhóm 2, Redis và Postman kiểm thử POST có đăng nhập vẫn cần tích hợp ở giai đoạn tiếp theo. Không triển khai thật bằng quyền MySQL `root`/mật khẩu rỗng.

Không `git push --force`. Giữ nhánh riêng, thử với bản sao dữ liệu, rồi mở Pull Request. Không commit backup chứa dữ liệu người dùng, khóa API hoặc `.env`.
