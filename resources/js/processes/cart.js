import $ from 'jquery';

let cart = [];


// ฟังก์ชันสำหรับ Render ตาราง
export function renderCart() {
    const tbody = document.querySelector('#cartTable tbody');
    if (!tbody) return;
    
    
    tbody.innerHTML = '';
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items added.</td></tr>';
        return;
    }

    cart.forEach((item, i) => {
        // แสดงข้อความ From Outlet ถ้ามีข้อมูล
        const sourceInfo = item.from_outlet_id 
            ? `<br><small class="text-primary">📦 From: ${item.from_outlet_text}</small>` 
            : '';

        tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <strong>${item.process_text}</strong> ${sourceInfo} <br>
                    <small class="text-muted">📅 ${item.date}</small>
                    
                    <input type="hidden" name="items[${i}][process_id]" value="${item.process_id}">
                    <input type="hidden" name="items[${i}][created_at]" value="${item.date}"> 
                    <input type="hidden" name="items[${i}][from_outlet_id]" value="${item.from_outlet_id}"> 
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
export function addItem(e) {
    if (e) e.preventDefault(); // ป้องกันการ reload หน้าจอ หรือ submit ฟอร์มโดยไม่ตั้งใจ

    const pSelect = document.getElementById('process_id_select');
    const uSelect = document.getElementById('product_unit_id_select');
    const qInput = document.getElementById('qty_input');
    const dInput = document.getElementById('transaction_date');
    const fOutletSelect = document.getElementById('from_outlet_id');

    const selectedOption = pSelect.options[pSelect.selectedIndex];
    const processName = selectedOption ? selectedOption.getAttribute('data-name') : '';

    // --- แก้ไขตรงนี้: เช็คว่าถ้าช่องว่างจริงๆ ถึงจะเตือน ---
    if (!pSelect.value || !uSelect.value || !qInput.value || qInput.value <= 0) {
        alert('กรุณาเลือก Process, สินค้า และระบุจำนวนให้ถูกต้อง');
        return;
    }

    if (processName === 'transfer' && !fOutletSelect.value) {
        alert('กรุณาเลือกสาขาต้นทาง (From Outlet)');
        return;
    }

    // เพิ่มข้อมูลลง cart
    cart.push({
        process_id: pSelect.value,
        process_text: selectedOption.text,
        process_name: processName,
        product_unit_id: uSelect.value,
        unit_text: uSelect.options[uSelect.selectedIndex].text,
        quantity: qInput.value,
        date: dInput.value,
        from_outlet_id: fOutletSelect.value || '',
        from_outlet_text: fOutletSelect.value ? fOutletSelect.options[fOutletSelect.selectedIndex].text : ''
    });

    renderCart();

    // ล้างค่าแค่เฉพาะ Product และ Qty (เพื่อให้ Process และ Date ยังค้างอยู่ เผื่อแอดรายการเดิมต่อ)
    $(uSelect).val(null).trigger('change');
    qInput.value = '';
}

// จัดการ Event เมื่อโหลดหน้าเว็บ

$(document).ready(function() {
    // 1. กดปุ่ม Add ให้เรียก addItem
    $('#btn-add-item').on('click', function(e) {
        addItem(e);
    });

    // 2. ป้องกันไม่ให้กด Enter ใน Input แล้วมันไปแจ้งเตือน (เพราะ Enter ในฟอร์มคือการ Submit)
    $('#qty_input').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            addItem(e);
        }
    });

    // 3. จัดการเปิด/ปิดช่อง From Outlet
    $('#process_id_select').on('change', function() {
        const processName = $(this).find(':selected').data('name');
        if (processName === 'transfer') {
            $('#from_outlet_section').slideDown();
        } else {
            $('#from_outlet_section').slideUp();
            $('#from_outlet_id').val('');
        }
    });
});