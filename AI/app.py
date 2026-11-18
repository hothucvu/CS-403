from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route('/api/auto-tag', methods=['POST'])
def auto_tag_item():
    # Nhận ảnh sản phẩm đồ cũ do người dùng tải lên
    if 'image' not in request.files:
        return jsonify({'error': 'Không tìm thấy file ảnh'}), 400
    
    file = request.files['image']
    
    # [Logic AI xử lý auto-tagging ở đây]
    # Ví dụ trả về kết quả giả lập cho Frontend/Backend gọi thử:
    mock_tags = ["Đồ điện tử", "Laptop cũ", "Lenovo"]
    
    return jsonify({
        'status': 'success',
        'suggested_tags': mock_tags
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)