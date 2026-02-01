import $ from 'jquery';

let cart = [];

// ฟังก์ชันสำหรับ Render ตาราง
export function renderCart() {
    const tbody = document.querySelector('#cartTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No items added.</td></tr>';
        return;
    }

    cart.forEach((item, i) => {
        tbody.innerHTML += `
            <tr>
            <td>${i + 1}</td>
            <td>
                ${item.process_text} <br>
                <small class="text-muted">📅 ${item.date}</small>
                <input type="hidden" name="items[${i}][process_id]" value="${item.process_id}">
                
                <input type="hidden" name="items[${i}][created_at]" value="${item.date}"> 
            </td>
            <td>${item.unit_text} <input type="hidden" name="items[${i}][product_unit_id]" value="${item.product_unit_id}"></td>
            <td>${item.quantity} <input type="hidden" name="items[${i}][quantity]" value="${item.quantity}"></td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-remove" data-index="${i}">✕</button>
            </td>
        </tr>`;
    });
}

// ฟังก์ชันสำหรับเพิ่มสินค้า
export function addItem() {
    const pSelect = document.getElementById('process_id_select');
    const uSelect = document.getElementById('product_unit_id_select');
    const qInput = document.getElementById('qty_input');
    const dInput = document.getElementById('transaction_date');

    if (!pSelect.value || !uSelect.value || !qInput.value) {
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }

    cart.push({
        process_id: pSelect.value,
        process_text: pSelect.options[pSelect.selectedIndex].text,
        product_unit_id: uSelect.value,
        unit_text: uSelect.options[uSelect.selectedIndex].text,
        quantity: qInput.value,
        date: dInput.value // 👈 เก็บวันที่ลงในอาเรย์
    });

    renderCart();

    // ล้างค่าสำหรับ Select2 และ Input
    $(uSelect).val(null).trigger('change');
    qInput.value = '';
}

// จัดการ Event เมื่อโหลดหน้าเว็บ
$(document).ready(function() { // 👈 Error เกิดตรงนี้เพราะมันไม่รู้จัก $
    $('#btn-add-item').on('click', addItem);

    // ผูกเหตุการณ์คลิกปุ่ม Remove (ใช้ Delegation เพราะปุ่มถูกสร้างใหม่เรื่อยๆ)
    $(document).on('click', '.btn-remove', function() {
        const index = $(this).data('index');
        cart.splice(index, 1);
        renderCart();
    });
    
    renderCart(); // รันครั้งแรกเพื่อโชว์สถานะว่าง
});