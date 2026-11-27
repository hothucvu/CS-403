/**
 * FE/assets/js/auto-tag.js  -  Nhóm 2
 * Khi người dùng chọn ảnh -> gọi BE/ai-autotag.php -> tự điền danh mục, tag, tiêu đề.
 * Nhóm 4 chỉ cần gọi initAutoTag(...) trong trang đăng bán (pages/create-product.php).
 */
function initAutoTag(opts) {
    const fileInput = document.querySelector(opts.fileInput);
    const categorySelect = opts.categorySelect ? document.querySelector(opts.categorySelect) : null;
    const tagsInput = opts.tagsInput ? document.querySelector(opts.tagsInput) : null;   // input name="ai_tags"
    const titleInput = opts.titleInput ? document.querySelector(opts.titleInput) : null;
    const statusEl = opts.statusEl ? document.querySelector(opts.statusEl) : null;
    const endpoint = opts.endpoint || '../BE/ai-autotag.php';
    if (!fileInput) return;

    const setStatus = (msg, isError) => {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.style.color = isError ? '#c0392b' : '#27ae60';
    };

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;

        const fd = new FormData();
        fd.append('image', file);
        setStatus('AI đang nhận diện ảnh...', false);

        try {
            const res = await fetch(endpoint, { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') {
                throw new Error(data.error || 'Không nhận diện được ảnh');
            }
            if (!data.confidence) {
                setStatus('AI chưa nhận ra món đồ trong ảnh, bạn hãy nhập tay nhé.', true);
                return;
            }

            if (categorySelect && data.category && data.category.id) categorySelect.value = data.category.id;
            if (tagsInput) tagsInput.value = JSON.stringify(data.suggested_tags);  // lưu vào products.ai_tags
            if (titleInput && !titleInput.value && data.title) titleInput.value = data.title;
            if (typeof opts.onResult === 'function') opts.onResult(data);  // để Nhóm 4 tự vẽ chip tag

            setStatus('AI đã gợi ý: ' + data.suggested_tags.join(', '), false);
        } catch (err) {
            setStatus(err.message, true);
        }
    });
}
