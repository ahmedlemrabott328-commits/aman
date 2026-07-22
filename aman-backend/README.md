# AMAN Backend — Laravel 12 API

هذا الجزء يمثل الـ Backend الكامل لمنصة **AMAN**، مبني على Laravel 12 وفق Clean Architecture و Repository Pattern و Service Layer، طبقًا للمواصفات الأصلية.

## 1. البنية المعمارية (Architecture Layers)

```
Request → Controller → FormRequest (Validation) → Service (Business Logic)
        → Repository (Data Access) → Eloquent Model → PostgreSQL
        ← API Resource (Response Shaping) ← Controller
```

كل طبقة مسؤولة عن شيء واحد فقط (SRP):

| الطبقة | المسؤولية | مثال |
|---|---|---|
| **Controller** | استقبال الطلب وإرجاع الرد فقط، بدون منطق أعمال | `TripController::store()` |
| **FormRequest** | التحقق من صحة المدخلات | `RequestTripRequest` |
| **Service** | منطق العمل (Business Rules) | `TripService::requestTrip()` |
| **Repository** | الوصول للبيانات عبر واجهة (Interface) قابلة للاستبدال | `TripRepositoryInterface` |
| **Model** | تمثيل الجدول والعلاقات فقط | `App\Models\Trip` |
| **Resource** | تشكيل الاستجابة النهائية (JSON) | `TripResource` |

الفائدة العملية: يمكن اختبار `TripService` بالكامل بدون قاعدة بيانات حقيقية (Mock للـ Repository)، ويمكن تغيير مصدر البيانات (مثلاً الانتقال لـ Elasticsearch لبحث الكباتن) دون لمس أي Controller.

## 2. التصميم المعياري (Modularity) — كيف تُضاف خدمة جديدة مستقبلاً؟

بدل بناء جدول `trips` بحقول لكل خدمة، تم اعتماد نمط **Extension Tables**:

- `trips`: الحقول المشتركة بين كل الخدمات (زبون، كابتن، موقع، سعر، حالة...).
- `trip_delivery_details`: حقول خاصة بالتوصيل فقط (اسم المستلم، حجم الطرد...).
- `trip_airport_details`: حقول خاصة بخدمة المطار فقط (رقم الرحلة الجوية...).

**لإضافة خدمة جديدة (مثلاً "نقل بضائع ثقيلة"):**
1. إضافة سطر في جدول `services`.
2. (اختياري) إنشاء migration لجدول `trip_<service>_details` إن احتاجت حقولاً خاصة.
3. إضافة Model + علاقة `hasOne` في `Trip`.
4. لا حاجة لتعديل `TripService`, `TripRepository`, أو أي Controller موجود.

هذا يحقق مبدأ **Open/Closed**: مفتوح للتوسع، مغلق للتعديل.

## 3. آلية إسناد الرحلات (Dispatch)

`TripDispatchService` يبحث عن أقرب كابتن ضمن دوائر متوسعة تدريجيًا: `2km → 5km → 10km → 15km` (قابلة للنقل لجدول `settings` لاحقًا لتُدار من لوحة الإدارة دون نشر كود جديد).

يعتمد البحث الجغرافي على **PostGIS** (`ST_DWithin`, `ST_Distance`) بدل حساب المسافة يدويًا بـ PHP لكل الكباتن، لأداء أعلى بكثير عند نمو عدد الكباتن.

**تنفيذ فعلي:** `TripDispatchService` يُستدعى الآن عبر `DispatchTripJob` (Queue) عند كل محاولة بحث، ويُبث `NewTripOffer` + Push فور ترشيح كابتن، مع `CaptainOfferTimeoutJob` لإدارة مهلة الاستجابة والانتقال للمرشح التالي تلقائيًا. التفاصيل الكاملة في القسم 8.

## 4. حالات الرحلة (State Machine)

```
requested → searching → accepted → arrived → in_progress → completed
                ↓            ↓         ↓
          no_captain_found  cancelled (من الزبون/الكابتن/الإدارة)
```

كل انتقال حالة يُسجَّل في `trip_status_history` عبر `TripService::logStatus()` — هذا يخدم متطلب الـ Audit Log وأيضًا تتبع الرحلة بدقة.

## 5. المدفوعات والمحفظة

طبقًا للمواصفات: الدفع مباشر من الزبون للكابتن (بدون بوابة دفع إلكتروني في الإصدار الأول). عند `completeTrip()`:
1. يُحسب السعر النهائي الفعلي (بالمسافة/الوقت الحقيقيين، ليس التقديريين).
2. تُحسب العمولة عبر `CommissionRule::calculateFor()`.
3. `WalletService::settleTrip()` يسجل حركتين بشكل ذري (DB Transaction + Row Lock): إضافة الأرباح، وخصم العمولة المستحقة — بحيث `wallet.balance` يعكس دائمًا صافي مستحقات/التزامات الكابتن تجاه المنصة.

## 6. RBAC

`Admin` ← (many-to-many) → `Role` ← (many-to-many) → `Permission`.
Middleware `permission:xxx` (مسجَّل في `Kernel.php`) يتحقق من الصلاحية على مستوى المسار مباشرة (انظر `routes/admin.php`) — لا حاجة لتكرار التحقق داخل كل Controller.

## 7. الحالة الحالية للكود (شفافية كاملة)

**مكتمل بالكامل ومختبَر منطقيًا:**
- Migrations لكل الجداول (29 جدول).
- Models مع كل العلاقات والـ Casts والثوابت.
- Repository Pattern كامل (Contracts + Eloquent) للكيانات الأساسية.
- Service Layer: `OtpService`, `PricingService`, `WalletService`, `TripDispatchService`, `TripService` (دورة حياة الرحلة الكاملة: طلب → بحث → قبول → وصول → بدء → إنهاء → إلغاء).
- Customer: Auth (OTP) + Trips Controllers كاملة مع Resources و FormRequests.
- Captain: Trips + Status Controllers كاملة.
- Routes: `api.php` (زبون/كابتن) و `admin.php` (هيكل كامل بصلاحيات RBAC).

**مكتمل الآن أيضًا (لوحة الإدارة):**
- `Admin\AuthController`: تسجيل دخول الأدمن (Sanctum) مع إرجاع الأدوار والصلاحيات.
- `Admin\DashboardController`: إحصائيات شاملة (زبائن، كباتن حسب الحالة، رحلات، إيرادات، تقسيم حسب الخدمة/اليوم).
- `Admin\CustomerController`: عرض/حظر الزبائن.
- `Admin\CaptainController`: عرض، الموافقة/الرفض/الإيقاف، مراجعة الوثائق (القسم 5 بالكامل).
- `Admin\TripController`, `Admin\PricingController`, `Admin\CommissionRuleController`, `Admin\CityController`, `Admin\WalletController` (بما فيها تعديل رصيد يدوي).
- `AuditLogService`: تُستدعى من كل عملية إدارية حساسة (حظر، اعتماد، تعديل تسعير، تعديل رصيد...) — تحقق القسم 15 فعليًا وليس فقط بالجدول.
- `RolesAndPermissionsSeeder` + `ServicesAndCitiesSeeder`: بيانات أولية جاهزة (أدوار: super_admin/operations/finance، حساب أدمن افتراضي `admin@aman.mr`، الخدمات الثلاث، مدينتا نواكشوط ونواذيبو).
- `bootstrap/app.php`: تسجيل middleware الصلاحيات `permission:xxx` وربط ملفي الـ routes.

**مكتمل الآن أيضًا (Realtime + Jobs + Push — تفاصيل كاملة في القسم 8):**
- Broadcasting عبر Reverb لكل انتقالات حالة الرحلة وموقع الكابتن اللحظي.
- Queue Jobs لتفعيل البحث الدوري الفعلي عن كابتن مع إدارة مهلة الاستجابة.
- Push Notifications (FCM) مربوطة بكل نقاط التحول الرئيسية في دورة حياة الرحلة واعتماد الكباتن.

**مكتمل الآن أيضًا (رفع الملفات الفعلي — تفاصيل كاملة في القسم 9):**
- رفع وثائق الكباتن الفعلي عبر `POST /captain/documents` (multipart) مع تخزين حقيقي على S3 (أو محلي للتطوير)، وروابط عرض مؤقتة وموقَّعة بدل روابط عامة دائمة.

**تبقّى (نطاق أصغر، غير حرج لتشغيل الـ MVP):**
- التوليد الفعلي لـ OAuth Access Token في `FcmService` عبر `google/auth` (حاليًا يقرأ توكن جاهز من `.env` لتسريع التطوير).
- ربط بوابة SMS فعلية في `OtpService::send()` (حاليًا `// TODO` واضح داخل الكود).
- `GET /services` و`GET /cities` العامة (لا تزال مُثبَّتة يدويًا في لوحة الإدارة وتطبيقي Flutter).
- Rate Limiting صريح على مسارات OTP.

## 8. الطبقات الثلاث المكتملة حديثًا (Realtime + Jobs + Push)

### 8.1 Broadcasting (Laravel Reverb)
- `App\Events\TripStatusChanged`: يُبث تلقائيًا من `TripService` عند كل انتقال حالة (قبول، وصول، بدء، إنهاء، إلغاء) على قناة خاصة `trip.{id}`.
- `App\Events\CaptainLocationUpdated`: يُبث من `StatusController::updateLocation` بمعدل عالٍ (بلا قائمة انتظار عبر `ShouldBroadcastNow`) طالما للكابتن رحلة نشطة، لتحريك أيقونته على خريطة الزبون لحظيًا دون Polling.
- `App\Events\NewTripOffer`: يُبث لقناة خاصة بالكابتن `captain.{id}` عند ترشيحه لرحلة، بالتوازي مع Push.
- `routes/channels.php`: التفويض يعتمد على أن Sanctum يُرجع مباشرة نموذج `Customer|Captain|Admin` الصحيح (tokenable polymorphic)، فيُقارَن بمالك الرحلة.
- **تشغيل الخادم فعليًا:** `php artisan reverb:start` (بعد `composer require laravel/reverb` وتعيين متغيرات `REVERB_*` في `.env`).

### 8.2 Queue Jobs (تفعيل البحث الدوري)
- `DispatchTripJob`: يُستدعى (أ) فور دخول رحلة فورية لحالة `searching`، (ب) فور رفض كابتن للعرض (استجابة أسرع بدل انتظار المهلة)، (ج) بعد انتهاء مهلة عرض بلا رد. يستدعي `TripService::attemptDispatch()` الذي أصبح يُرجع المرشح (لا `void`)، ثم يبث `NewTripOffer` ويرسل Push، ثم يجدول `CaptainOfferTimeoutJob` بعد 15 ثانية.
- `CaptainOfferTimeoutJob`: إن لم يستجب الكابتن خلال المهلة، يُعلّم المحاولة `timeout` ويعيد تشغيل `DispatchTripJob` للمرشح التالي — هذا هو التنفيذ الفعلي لـ"توسيع دائرة البحث تدريجيًا" من القسم 7.
- `routes/console.php`: جدولة `aman:prune-expired-otps` يوميًا (أمر `PruneExpiredOtps` بديل عن "ExpireOtpJob" منفصل — التنظيف الجماعي اليومي أكفأ من job لكل رمز)، وجدولة كل 5 دقائق لتفعيل الرحلات المجدولة القريبة من موعدها (`dueScheduledTrips()`).
- **تشغيل فعلي:** يحتاج `php artisan queue:work` (للـ Jobs) + إضافة `* * * * * php artisan schedule:run` على cron الخادم (للجدولة).

### 8.3 Push Notifications (FCM)
- `NotificationService`: نقطة مركزية واحدة (`notifyCustomer` / `notifyCaptain`) تُسجّل الإشعار في جدول `notifications` **و** ترسله فوريًا عبر `FcmService`. مُستدعاة الآن من: `TripService` (كل انتقال حالة)، `DispatchTripJob` (عرض رحلة جديد + عدم إيجاد كابتن)، و`Admin\CaptainController` (اعتماد/رفض الحساب).
- `FcmService`: يستخدم FCM HTTP v1 API. توليد الـ OAuth Access Token مبسّط حاليًا (`config('services.fcm.access_token')`)؛ **يحتاج قبل الإنتاج** استبداله بتوليد فعلي عبر `google/auth` وملف Service Account (المسار موضّح في تعليق داخل الكود).

## 9. رفع الملفات الفعلي (وثائق الكباتن)

### 9.1 التصميم

- **`DocumentStorageService`** نقطة مركزية واحدة لكل عمليات التخزين: `store()` يحفظ الملف باسم عشوائي (UUID، ليس اسم الملف الأصلي أبدًا) تحت مسار `captain-documents/{captainId}/{documentType}/`، و`temporaryUrl()` يولّد رابط عرض صالحًا لمدة محدودة فقط (15 دقيقة افتراضيًا، قابلة للتعديل عبر `DOCUMENT_URL_TTL_MINUTES`).
- **لماذا روابط مؤقتة وليست عامة؟** وثائق الكابتن (بطاقة وطنية، رخصة قيادة) بيانات هوية حسّاسة. تخزينها كملفات عامة دائمة الرابط يعني أن أي شخص يحصل على الرابط ولو مرة واحدة يستطيع الوصول إليها للأبد. القرص `s3` في `config/filesystems.php` مضبوط `'visibility' => 'private'` صراحة لهذا السبب.
- **عمود `file_path` بدل `file_url`:** أعدنا تسمية العمود (migration `rename_file_url_to_file_path...`) ليعكس أنه يخزّن مسارًا داخليًا وليس رابطًا قابلاً للاستخدام مباشرة. **حقل الاستجابة في الـ API بقي `file_url`** (في `CaptainDocumentResource`) عمدًا — العقد الخارجي (Contract) لم يتغيّر، فقط طريقة تعبئته داخليًا، بحيث لا تحتاج تطبيقات Flutter أو لوحة الإدارة لأي تعديل في أسماء الحقول التي تقرؤها.
- **بديل تطويري محلي:** إن كان `FILESYSTEM_DISK=local` (بيئة تطوير بلا حساب S3)، يُستخدَم مسار `GET /documents/preview/{path}` (محمي بـ `auth:sanctum` + تحقق ملكية داخل `DocumentPreviewController`) بدل `temporaryUrl` الحقيقي من S3.

### 9.2 نقاط النهاية (Endpoints)

| Method | المسار | الوصف |
|---|---|---|
| GET | `/captain/documents` | قائمة وثائق الكابتن الحالي مع حالتها |
| POST | `/captain/documents` | رفع وثيقة جديدة (multipart: `document_type`, `file`, `expires_at?`) |

كل رفع جديد لنفس `document_type` يُنشئ سجلاً جديدًا (وليس تعديل القديم)، فتحتفظ الإدارة بأثر تاريخي كامل لكل محاولة رفع سابقة.

### 9.3 خطوة إنتاج متبقية

`league/flysystem-aws-s3-v3` أُضيفت إلى `composer.json` لكنها تحتاج `composer install` فعليًا على الخادم، وملء متغيرات `AWS_*` في `.env` (تعمل مع أي مزوّد متوافق مع S3 API، وليس AWS حصرًا — راجع `AWS_ENDPOINT` للاستضافة المحلية).

## 10. التشغيل محليًا (محدَّث)

```bash
composer install
cp .env.example .env
php artisan key:generate
# تأكد من تفعيل PostGIS: CREATE EXTENSION postgis; على قاعدة بيانات pgsql
php artisan migrate --seed

# 3 عمليات منفصلة يجب أن تعمل معًا:
php artisan serve                 # API
php artisan reverb:start          # WebSocket (Realtime)
php artisan queue:work            # معالجة DispatchTripJob وغيرها
```

## 11. هيكل المجلدات (محدَّث)

```
app/
├── Models/                    # 26 Model
├── Repositories/{Contracts,Eloquent}/
├── Services/                  # OtpService, TripService, TripDispatchService,
│                               # WalletService, PricingService, NotificationService,
│                               # FcmService, AuditLogService, DocumentStorageService
├── Events/                    # TripStatusChanged, CaptainLocationUpdated, NewTripOffer
├── Jobs/                      # DispatchTripJob, CaptainOfferTimeoutJob
├── Console/Commands/          # PruneExpiredOtps
├── Http/
│   ├── Controllers/Api/V1/{Customer,Captain,Admin}/   # كل الـ Controllers مكتملة
│   ├── Requests/{Customer,Captain,Admin}/
│   ├── Resources/
│   └── Middleware/             # CheckPermission (RBAC)
├── Providers/RepositoryServiceProvider.php
└── Exceptions/                 # OtpException, TripException
database/{migrations,seeders}/  # 29 migration + Roles/Permissions/Cities/Services seeders
routes/
├── api.php                     # مسارات الزبون والكابتن
├── admin.php                   # مسارات لوحة الإدارة (RBAC)
├── channels.php                # تفويض قنوات البث
└── console.php                 # الجدولة الدورية
bootstrap/app.php                # تسجيل middleware + broadcasting + routing
config/{broadcasting,services}.php
```
