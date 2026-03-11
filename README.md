<p align="center">
<a href="#"><img src="https://img.icons8.com/external-flat-icons-inmotus-design/512/external-bar-bar-flat-icons-inmotus-design.png" width="220" alt="StockSip Logo"></a>
</p>

<h1 align="center">🍷 StockSip</h1>
<p align="center">
ระบบจัดการคลังเครื่องดื่มออนไลน์ เพื่อสนับสนุนการคำนวณค่า PAR (Periodic Automatic Replenishment)
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-Framework-red">
<img src="https://img.shields.io/badge/PHP-Backend-blue">
<img src="https://img.shields.io/badge/MySQL-Database-orange">
<img src="https://img.shields.io/badge/License-MIT-green">
</p>

---

## 📌 About the Project

**StockSip** พัฒนาขึ้นเพื่อแก้ปัญหาการจัดการคลังเครื่องดื่มใน **โรงแรมและบาร์**  
ซึ่งมักพบปัญหา เช่น

- การบันทึกสต็อกแบบ Manual ทำให้เกิดความคลาดเคลื่อน
- การแปลงหน่วยสินค้า เช่น **ขวด → แก้ว** ทำได้ยาก
- การคำนวณค่า **PAR Stock** ใช้เวลานาน

ระบบนี้ช่วยให้การจัดการคลังมี **ความแม่นยำมากขึ้น ลดเวลาการทำงาน และช่วยตัดสินใจในการเติมสินค้าได้เร็วขึ้น**

---

## 🌟 Key Features

### 📊 Real-time Dashboard
แจ้งเตือนสถานะสินค้าที่ต่ำกว่าค่า **PAR Level** ทันทีด้วยแถบสี

### 🔄 Smart Unit Conversion
ระบบ Helper สำหรับแปลงหน่วยสินค้าอัตโนมัติ  
เช่น **ขวด → แก้ว** เพื่อให้การตัดสต็อกมีความแม่นยำ

### 📦 Inventory Management
รองรับการทำรายการ

- Sell (ขาย)
- Transfer (โอนย้ายสาขา)
- Spoil (สินค้าชำรุด / เปิดใช้)

### ✅ Approval System
ระบบอนุมัติรายการโดย **หัวหน้างาน**  
เพื่อเพิ่มความโปร่งใสและตรวจสอบย้อนหลังได้

### 🏪 Multi-Outlet Support
รองรับการจัดการคลังสินค้า **หลายสาขา / หลายจุดจำหน่าย**

---

## 🚀 Project Results

จากการทดลองใช้งานกับกลุ่มตัวอย่าง

**⏱ Time Efficiency**

- เวลาทำ PAR เดิม : **25 นาที**
- หลังใช้ระบบ : **6.11 นาที**

ลดเวลาได้ประมาณ **75%**

**⭐ User Satisfaction**

คะแนนความพึงพอใจเฉลี่ย

**4.89 / 5.00 (ระดับมากที่สุด)**

---

## 🛠️ Tech Stack

**Backend**

- PHP
- Laravel Framework

**Frontend**

- React
- Blade Template
- Tailwind CSS

**Database**

- MySQL  
- ออกแบบโครงสร้างแบบ **Star Schema**

**Architecture**

- MVC (Model-View-Controller)

---

## 📦 Installation

### 1️⃣ Clone Project

```bash
git clone https://github.com/Lotte700/stocksip.git