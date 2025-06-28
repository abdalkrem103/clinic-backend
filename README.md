# نظام إدارة العيادة - الخلفية (Backend)

## 📋 الوصف
خلفية نظام إدارة العيادة مبنية بـ PHP و MySQL، توفر API كامل لإدارة العيادة.

## 🛠️ التقنيات المستخدمة
- **PHP 8.x** - لغة البرمجة
- **MySQL** - قاعدة البيانات
- **PHPMailer** - إرسال البريد الإلكتروني
- **Railway** - منصة الاستضافة

## 🚀 التثبيت والتشغيل

### المتطلبات
- PHP 8.0 أو أحدث
- MySQL 8.0 أو أحدث
- Composer

### التثبيت المحلي
```bash
# استنساخ المشروع
git clone [repository-url]
cd clinic-backend

# تثبيت التبعيات
composer install

# إعداد ملف البيئة
cp .env.example .env
# تعديل متغيرات البيئة

# إنشاء قاعدة البيانات
mysql -u root -p < clinic_management.sql

# تشغيل الخادم المحلي
php -S localhost:8000 -t public
```

## 📁 بنية المشروع
```
├── api/                 # ملفات API
│   ├── config.php      # إعدادات قاعدة البيانات
│   ├── cors.php        # إعدادات CORS
│   ├── login.php       # تسجيل الدخول
│   └── ...            # باقي نقاط النهاية
├── public/             # نقطة الدخول للخادم
│   └── index.php
├── uploads/            # الملفات المرفوعة
│   └── xrays/         # صور الأشعة
├── vendor/             # تبعيات Composer
├── composer.json       # تبعيات PHP
├── railway.json        # إعدادات Railway
└── nixpacks.toml      # إعدادات البناء
```

## 🔗 نقاط النهاية API

### المصادقة
- `POST /login` - تسجيل دخول المدير
- `POST /login_patient` - تسجيل دخول المريض
- `POST /register_patient` - تسجيل مريض جديد

### إدارة المستخدمين
- `GET /get_users` - جلب جميع المستخدمين
- `POST /add_user` - إضافة مستخدم جديد
- `PUT /update_user` - تحديث مستخدم
- `DELETE /delete_user` - حذف مستخدم

### إدارة الأطباء
- `GET /doctors` - جلب جميع الأطباء
- `POST /doctors` - إضافة طبيب جديد
- `PUT /doctors` - تحديث طبيب
- `DELETE /doctors` - حذف طبيب

### إدارة المواعيد
- `GET /appointments` - جلب المواعيد
- `POST /appointments` - إنشاء موعد جديد
- `PUT /appointments` - تحديث موعد
- `DELETE /appointments` - حذف موعد

### إدارة الخدمات
- `GET /services` - جلب الخدمات
- `POST /services` - إضافة خدمة جديدة
- `PUT /services` - تحديث خدمة
- `DELETE /services` - حذف خدمة

## 🔧 متغيرات البيئة
```env
DB_HOST=localhost
DB_NAME=clinic_management
DB_USER=root
DB_PASS=password

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

## 🚀 النشر على Railway
1. رفع الكود إلى GitHub
2. ربط المشروع بـ Railway
3. إضافة قاعدة بيانات MySQL
4. إعداد متغيرات البيئة
5. النشر التلقائي

## 📞 الدعم
في حالة مواجهة أي مشاكل، يرجى مراجعة:
- سجلات الأخطاء
- إعدادات قاعدة البيانات
- متغيرات البيئة
- إعدادات CORS

## 📄 الترخيص
هذا المشروع مملوك للعيادة ولا يجوز استخدامه بدون إذن. 