# تعليمات النشر - نظام إدارة العيادة (الخلفية)

## 🚀 النشر على Railway

### الخطوة 1: إنشاء حساب على Railway
1. اذهب إلى [railway.app](https://railway.app)
2. سجل دخول باستخدام GitHub
3. احصل على رصيد مجاني ($5)

### الخطوة 2: رفع الكود إلى GitHub
```bash
# انتقل إلى مجلد الخلفية
cd backend

# تهيئة Git
git init
git add .
git commit -m "Initial commit - Clinic Management Backend"

# ربط المستودع المحلي بـ GitHub
git remote add origin https://github.com/YOUR_USERNAME/clinic-backend.git
git branch -M main
git push -u origin main
```

### الخطوة 3: ربط المشروع بـ Railway
1. في Railway، اضغط "New Project"
2. اختر "Deploy from GitHub repo"
3. اختر مستودع `clinic-backend`
4. اضغط "Deploy Now"

### الخطوة 4: إضافة قاعدة بيانات MySQL
1. في مشروع Railway، اضغط "New"
2. اختر "Database" → "MySQL"
3. انتظر حتى يتم إنشاء قاعدة البيانات
4. احفظ معلومات الاتصال

### الخطوة 5: إعداد متغيرات البيئة
في Railway، اذهب إلى "Variables" وأضف:

```env
# Database Configuration
DB_HOST=YOUR_RAILWAY_MYSQL_HOST
DB_NAME=YOUR_RAILWAY_MYSQL_DATABASE
DB_USER=YOUR_RAILWAY_MYSQL_USER
DB_PASS=YOUR_RAILWAY_MYSQL_PASSWORD
DB_PORT=YOUR_RAILWAY_MYSQL_PORT

# Email Configuration (Gmail SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_SECURE=tls

# Application Configuration
APP_NAME=Clinic Management System
APP_URL=https://your-app.railway.app
APP_ENV=production

# Security
JWT_SECRET=your-super-secret-jwt-key-here
ENCRYPTION_KEY=your-32-character-encryption-key

# CORS Settings
ALLOWED_ORIGINS=https://clinic-management-smoky.vercel.app,http://localhost:3000
```

### الخطوة 6: رفع قاعدة البيانات
1. في Railway، اذهب إلى قاعدة البيانات
2. اضغط "Connect" → "MySQL"
3. انسخ معلومات الاتصال
4. استخدم أي أداة MySQL لرفع الملف `clinic_management.sql`

### الخطوة 7: اختبار النشر
1. انتظر حتى يكتمل النشر
2. اذهب إلى رابط التطبيق
3. يجب أن ترى رسالة "Clinic Management API is running"

## 🔧 استكشاف الأخطاء

### مشاكل شائعة:
1. **خطأ في قاعدة البيانات**: تحقق من متغيرات البيئة
2. **خطأ CORS**: تحقق من `ALLOWED_ORIGINS`
3. **خطأ في البريد**: تحقق من إعدادات SMTP
4. **خطأ في الملفات**: تحقق من صلاحيات مجلد `uploads`

### سجلات الأخطاء:
- في Railway، اذهب إلى "Deployments"
- اضغط على آخر نشر
- راجع "Logs" للعثور على الأخطاء

## 📞 الدعم
إذا واجهت أي مشاكل:
1. راجع سجلات الأخطاء في Railway
2. تحقق من متغيرات البيئة
3. تأكد من صحة معلومات قاعدة البيانات
4. راجع إعدادات CORS

## 🔗 روابط مفيدة
- [Railway Documentation](https://docs.railway.app/)
- [PHP on Railway](https://docs.railway.app/deploy/deployments/languages/php)
- [MySQL on Railway](https://docs.railway.app/databases/mysql) 