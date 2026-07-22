import 'package:flutter/material.dart';

/// نظام ألوان AMAN — نفس الهوية المعتمدة في لوحة الإدارة (React):
/// نيلي مستوحى من صبغة النيلة الموريتانية التقليدية + رملي دافئ + ذهبي للتمييز.
/// الحفاظ على هوية بصرية موحّدة عبر كل تطبيقات AMAN (Admin/Captain/Customer) مقصود.
class AppColors {
  AppColors._();

  static const Color indigo900 = Color(0xFF0F1326);
  static const Color indigo700 = Color(0xFF1D264A);
  static const Color indigo500 = Color(0xFF2E3A6E); // اللون الأساسي للعلامة
  static const Color indigo300 = Color(0xFF8590CC);
  static const Color indigo100 = Color(0xFFD6DAF0);
  static const Color indigo50 = Color(0xFFEEF0F8);

  static const Color sand = Color(0xFFF6F1E7);       // خلفية التطبيق
  static const Color sandDim = Color(0xFFECE4D3);
  static const Color sandDeep = Color(0xFFDCCFAE);

  static const Color gold = Color(0xFFC1922E);        // الحالة "متصل" وأزرار التمييز
  static const Color goldLight = Color(0xFFE0B863);
  static const Color goldDark = Color(0xFF96701F);

  static const Color teal = Color(0xFF2F6F62);        // نجاح / اعتماد / أرباح
  static const Color tealLight = Color(0xFFE3EFEC);

  static const Color terracotta = Color(0xFFB5533C);  // خطر / رفض / إلغاء
  static const Color terracottaLight = Color(0xFFF5E6E1);

  static const Color ink = Color(0xFF1A2340);          // نص أساسي
  static const Color inkSoft = Color(0xFF5B6178);       // نص ثانوي
}
