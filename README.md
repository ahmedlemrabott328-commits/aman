# AMAN Admin — لوحة إدارة (React + TypeScript + Tailwind)

لوحة إدارة كاملة لمنصة AMAN، مبنية لتعمل مباشرة مع [aman-backend](../aman-backend) (Laravel API).

## الهوية البصرية

- **الألوان:** نيلي (`#2E3A6E`) مستوحى من صبغة النيلة الموريتانية التقليدية + رملي دافئ (`#F6F1E7`) + ذهبي (`#C1922E`) للتمييز. راجع `tailwind.config.js`.
- **الطباعة:** Cairo للواجهة (عربي/لاتيني)، IBM Plex Mono للأرقام والمبالغ وأكواد الرحلات (فئة `.tabular` في `index.css`).
- **الاتجاه:** RTL افتراضيًا (`<html dir="rtl">`)، الشريط الجانبي على اليمين.

## البنية

```
src/
├── types/index.ts          # أنواع مطابقة تمامًا لـ API Resources في aman-backend
├── lib/api.ts               # عميل Axios مركزي (توكن تلقائي + معالجة 401)
├── hooks/useApi.ts          # usePaginatedResource / useResource (بديل خفيف عن react-query)
├── contexts/AuthContext.tsx # جلسة الأدمن + نظام الصلاحيات (can(permission))
├── components/
│   ├── ui/                  # Button, Badge, Card, Input, Select, Modal, Table, Pagination
│   ├── layout/               # Sidebar (يُخفي العناصر حسب الصلاحية تلقائيًا)، Topbar، AppLayout
│   └── ProtectedRoute.tsx
└── pages/                    # صفحة لكل قسم من قسم 13 بالمواصفات
    ├── LoginPage, DashboardPage, CustomersPage, CaptainsPage
    ├── TripsPage, PricingPage (تسعير + عمولات بتبويبين), CitiesPage, WalletsPage
```

## نقاط تصميمية مهمة

- **الصلاحيات (RBAC) في الواجهة أيضًا:** `Sidebar` يُخفي أي رابط لا يملك الأدمن الحالي صلاحيته (`can('captains.view')`)، مطابقةً لما يفرضه Backend فعليًا على مستوى الـ API — الواجهة لا "تخترع" صلاحيات، فقط تعكس ما يُرجعه `/auth/login`.
- **اعتماد الكباتن:** `CaptainsPage` تفتح لوحة تفاصيل كاملة (Modal) تعرض كل وثيقة مع إمكانية قبول/رفض كل وثيقة على حدة قبل تفعيل زر "اعتماد الكابتن" — يعكس تدفق العمل الحقيقي (لا يمكن الاعتماد دون مراجعة الوثائق أولاً، وهذا مفروض أيضًا من Backend: `PATCH /captains/{id}/approve` يرفض الطلب إن كانت أي وثيقة غير معتمدة).
- **تعديل الرصيد اليدوي:** موجود في `WalletsPage` مع تنويه صريح أن كل تعديل يُسجَّل في Audit Log — شفافية للمستخدم بدل تعديل صامت.

## القيود المعروفة (Known Limitations)

- **قائمة الخدمات (Services) مُثبَّتة يدويًا** في `PricingPage.tsx` (`SERVICES` بالمعرفات 1/2/3) لعدم وجود `GET /services` في الـ API الحالي. إن تغيّر ترتيب الإدخال في `ServicesAndCitiesSeeder`، يجب تحديث هذا الثابت أو إضافة endpoint فعلي.
- لا يوجد بعد اختبارات (Unit/E2E) — يُنصح بإضافة Vitest + Testing Library قبل الإنتاج.
- التوطين (i18n) الفعلي لثلاث اللغات (AR/FR/EN) غير مُفعَّل بعد؛ كل النصوص حاليًا عربية مباشرة داخل المكوّنات. البنية (RTL + الخط) جاهزة لإضافة `react-i18next` لاحقًا دون إعادة هيكلة.

## التشغيل محليًا

```bash
npm install
cp .env.example .env   # عدّل VITE_API_URL إن كان الـ Backend على منفذ/نطاق مختلف
npm run dev
```

الدخول التجريبي (من `RolesAndPermissionsSeeder` في aman-backend): `admin@aman.mr` / `ChangeMe123!` — **غيّر كلمة المرور فورًا في الإنتاج**.
