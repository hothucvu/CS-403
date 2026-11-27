"""Gọi thử service:  python test_autotag.py duong_dan_anh.jpg
(cần: pip install requests, và đã chạy python app.py)"""
import json
import sys

import requests

BASE = "http://127.0.0.1:5000"
CATEGORIES = [{"id": 1, "name": "Đồ điện tử"}, {"id": 2, "name": "Thời trang"},
              {"id": 3, "name": "Đồ gia dụng"}, {"id": 4, "name": "Nội thất"}]

if len(sys.argv) < 2:
    sys.exit("Cách dùng: python test_autotag.py <anh.jpg>")

print("health:", requests.get(f"{BASE}/health").json())
with open(sys.argv[1], "rb") as f:
    r = requests.post(f"{BASE}/api/auto-tag",
                      files={"image": (sys.argv[1], f, "image/jpeg")},
                      data={"categories": json.dumps(CATEGORIES)})
print(r.status_code, json.dumps(r.json(), ensure_ascii=False, indent=2))
