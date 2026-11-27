document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.getElementById("homeSearchForm");
    const searchInput = document.getElementById("searchInput");
    const resetSearchBtn = document.getElementById("resetSearchBtn");

    if (searchForm && searchInput) {
        searchForm.addEventListener("submit", function (event) {
            const keyword = searchInput.value.trim();
            if (keyword === "") {
                event.preventDefault();
                window.location.href = "layout.php?page=home";
            } else {
                searchInput.value = keyword;
            }
        });
    }

    if (resetSearchBtn) {
        resetSearchBtn.addEventListener("click", function () {
            window.location.href = "layout.php?page=home";
        });
    }
});