<h1 align="center">🍷 StockSip</h1>

<p align="center">
Online Beverage Inventory Management System for PAR (Periodic Automatic Replenishment)
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-Framework-red">
<img src="https://img.shields.io/badge/PHP-Backend-blue">
<img src="https://img.shields.io/badge/MySQL-Database-orange">
</p>

---

## 📌 About the Project

**StockSip** is an online beverage inventory management system designed to solve common inventory problems in **hotels and bars**.

Many businesses still manage stock manually, which often leads to:

- Inventory inaccuracies from manual recording
- Difficulty converting product units (e.g., **bottle → glass**)
- Time-consuming **PAR stock calculations**

StockSip helps improve **inventory accuracy**, **reduce operational time**, and **support better replenishment decisions**.

---

## 🌟 Key Features

### 📊 Real-time Dashboard
Displays inventory status and highlights items **below PAR level** using color indicators.

### 🔄 Smart Unit Conversion
Automatic unit conversion helper  
(e.g., **Bottle → Glass**) to ensure accurate stock deduction.

### 📦 Inventory Management
Supports multiple inventory operations:

- **Sell** – Record beverage sales
- **Transfer** – Move stock between outlets
- **Spoil** – Record damaged or opened items

### ✅ Approval System
Transactions must be approved by a **manager** to maintain **data integrity and transparency**.

### 🏪 Multi-Outlet Support
Supports inventory management across **multiple outlets or branches**.

---

## 🚀 Project Results

System testing with sample users showed significant improvements.

### ⏱ Time Efficiency

- Original PAR calculation time: **25 minutes**
- Using StockSip: **6.11 minutes**

Time reduced by approximately **75%**

### ⭐ User Satisfaction

Average satisfaction score:

**4.89 / 5.00 (Excellent)**

---

## 🛠 Tech Stack

### Backend
- PHP
- Laravel Framework

### Frontend
- React
- Blade Template
- Tailwind CSS

### Database
- MySQL  
- Designed using **Star Schema**

### Architecture
- MVC (Model–View–Controller)

---

## 📦 Installation

### 1️⃣ Clone Project

```bash
git clone https://github.com/Lotte700/stocksip.git
cd stocksip
```

### 2️⃣ Install Dependencies

```bash
composer install
npm install
```

### 3️⃣ Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database settings inside the `.env` file.

---

### 4️⃣ Database Setup

```bash
php artisan migrate --seed
```

---

### 5️⃣ Run Server

```bash
php artisan serve
npm run dev
```

---

## 💻 Core Logic Example

Example helper function used to convert inventory units.

```php
public static function formatUnit($total_quantity, $conversion_rate)
{
    $bottles = floor($total_quantity / $conversion_rate);
    $glasses = $total_quantity % $conversion_rate;

    return "{$bottles} bottles, {$glasses} glasses";
}
```

---

## 👨‍💻 Author

**Kitipat Panchang**

Modern Management and Information Technology (MMIT)  
College of Arts, Media and Technology  
Chiang Mai University

---

## 📄 License

This project is licensed under the **MMIT License Up date later for proposal**.