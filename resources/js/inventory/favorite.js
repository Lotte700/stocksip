$(document).ready(function() {
    // ใช้ Delegation เพื่อให้รองรับกรณีมีการโหลดตารางใหม่แบบ AJAX
    $(document).on('click', '.focus-toggle-btn', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const productId = btn.data('product-id');
        const icon = btn.find('i');
        // ดึง URL จาก attribute เพื่อไม่ให้โค้ด JS ยึดติดกับเส้นทางตายตัว
        const toggleUrl = btn.data('url'); 
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        btn.css('pointer-events', 'none');

        $.ajax({
            url: toggleUrl,
            method: 'POST',
            data: {
                _token: csrfToken,
                product_id: productId
            },
            success: function(response) {
                if (response.status === 'added') {
                    icon.removeClass('bi-star text-muted').addClass('bi-star-fill text-warning');
                } else {
                    icon.removeClass('bi-star-fill text-warning').addClass('bi-star text-muted');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            },
            complete: function() {
                btn.css('pointer-events', 'auto');
            }
        });
    });
});