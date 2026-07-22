# AMAN Captain — تطبيق الكابتن (Flutter)

تطبيق الكابتن الكامل لمنصة AMAN، Material Design 3، يعمل مباشرة مع [aman-backend](../aman-backend).

## البنية (Clean Architecture حسب الميزة)

```
lib/
├── core/
│   ├── theme/            # AppColors + AppTheme (نفس هوية AMAN: نيلي/رملي/ذهبي، Cairo + IBM Plex Mono)
│   ├── network/           # ApiClient (Dio) + ApiException موحّد
│   ├── storage/            # TokenStorage (flutter_secure_storage)
│   ├── realtime/           # RealtimeService (Reverb عبر pusher_channels_flutter)
│   ├── router/             # GoRouter + حراسة الجلسة التلقائية
│   └── providers.dart      # حقن الاعتماديات المركزي (DI عبر Riverpod)
└── features/
    ├── auth/               # OTP: إدخال الهاتف → التحقق → تسجيل الدخول
    ├── documents/          # رفع وثائق الاعتماد (رخصة، بطاقة، استمارة، تأمين)
    ├── home/               # الخريطة + مفتاح متصل/غير متصل + استقبال عروض الرحلات لحظيًا
    ├── trip/                # الرحلة النشطة: قبول → وصول → بدء → إنهاء يدويًا
    ├── wallet/              # الرصيد وسجل الحركات
    ├── history/             # سجل الرحلات السابقة
    └── profile/             # الملف الشخصي وتسجيل الخروج
```

كل ميزة مقسّمة إلى `data` (Repository) / `domain` (Models) / `presentation` (Controller + Screens)، وكل الاعتماديات محقونة عبر Riverpod Providers بدل الإنشاء المباشر — يخدم مبدأي Dependency Injection وSOLID المطلوبين بالمواصفات.

## تدفق العمل الأساسي (يطابق القسم 5 من المواصفات)

1. **تسجيل الدخول (OTP):** `PhoneEntryScreen` → `OtpVerifyScreen` → عند النجاح يُخزَّن التوكن في Secure Storage ويُعاد التوجيه تلقائيًا (`GoRouter.redirect` يراقب `AuthState` لحظيًا).
2. **رفع الوثائق:** `DocumentsScreen` — 4 وثائق أساسية (رخصة، بطاقة وطنية، استمارة، تأمين)، كل وثيقة تُرفع وتظهر حالتها (بانتظار المراجعة) فور الرفع.
3. **الاتصال/عدم الاتصال:** مفتاح في `HomeScreen` يستدعي `/status/toggle`، ويُفعِّل بث الموقع كل 20 مترًا عبر `Geolocator.getPositionStream` عند التفعيل.
4. **استقبال طلب رحلة:** `RealtimeService` يشترك في القناة الخاصة `captain.{id}`؛ فور وصول `trip.offer.new` تظهر `TripOfferSheet` بعدّاد تنازلي مطابق تمامًا لمهلة الخادم (15 ثانية)، مع قبول/رفض.
5. **الرحلة النشطة:** `TripActiveScreen` تعرض بيانات الزبون (مع زر اتصال مباشر `tel:`)، والعنوان، والسعر، وزر الإجراء يتغيّر تلقائيًا حسب حالة الرحلة (وصلت → بدء → إنهاء).
6. **الأرباح والمحفظة:** `WalletScreen` تعرض الرصيد الحالي (بخط IBM Plex Mono للوضوح الرقمي) وسجل الحركات.

## القيود المعروفة (بصراحة كاملة)

- **رفع الملفات الفعلي مكتمل الآن.** `DocumentsScreen` يلتقط صورة عبر `image_picker` ويرفعها فعليًا عبر `multipart/form-data` إلى `POST /documents`، والخادم (`DocumentStorageService`) يخزّنها على S3 (أو محليًا للتطوير) ويعيد بيانات الوثيقة مع رابط عرض مؤقت وموقَّع. راجع القسم 9 في README الخاص بـ `aman-backend` للتفاصيل الكاملة.
- **`pusher_channels_flutter` API قابلة للتغيّر بين الإصدارات.** التوقيعات المستخدمة في `RealtimeService` مطابقة للإصدار `^2.4.1` وقت الكتابة؛ تحقّق من التوثيق الفعلي عند `flutter pub get` إن ظهرت أخطاء توافق.
- **لا يوجد endpoint `/me`** لاسترجاع بيانات الكابتن من التوكن مباشرة عند إعادة فتح التطبيق؛ حاليًا إن وُجد توكن مخزَّن يُعتبر صالحًا مبدئيًا، ويُعاد التوجيه لتسجيل الدخول فقط عند أول رد `401` فعلي من الخادم.
- **لا توجد اختبارات بعد** (Unit/Widget) — يُنصح بإضافتها قبل الإنتاج، خصوصًا لمنطق `TripController` و`HomeController`.
- **خرائط OpenStreetMap مجانية** (`flutter_map` + tile.openstreetmap.org) بدل Google Maps لتفادي الحاجة لمفتاح API مدفوع؛ يمكن استبدالها لاحقًا إن رغبت بمزايا Google (Traffic، ETA أدق).

## التشغيل محليًا

```bash
flutter pub get

# على محاكي أندرويد، 10.0.2.2 تُترجم إلى localhost على جهاز التطوير (القيمة الافتراضية جاهزة)
flutter run \
  --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1/captain \
  --dart-define=BROADCAST_AUTH_URL=http://10.0.2.2:8000/broadcasting/auth \
  --dart-define=REVERB_HOST=10.0.2.2 \
  --dart-define=REVERB_PORT=8080 \
  --dart-define=REVERB_APP_KEY=<REVERB_APP_KEY من .env الخاص بـ aman-backend>
```

على جهاز/محاكي iOS استبدل `10.0.2.2` بعنوان IP الفعلي لجهاز التطوير على الشبكة المحلية.
