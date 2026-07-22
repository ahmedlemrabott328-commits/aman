import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_colors.dart';

/// ثيم AMAN — Material Design 3 مع خط Cairo للواجهة العامة.
/// الأرقام والمبالغ يجب أن تُعرض دائمًا بخط IBM Plex Mono (راجع AppTextStyles.tabular)
/// لوضوح رقمي حقيقي في شاشات الأرباح والمحفظة، بدل استخدام نفس خط الواجهة للجميع.
class AppTheme {
  AppTheme._();

  static ThemeData get light {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.indigo500,
        primary: AppColors.indigo500,
        secondary: AppColors.gold,
        surface: Colors.white,
        error: AppColors.terracotta,
        brightness: Brightness.light,
      ),
      scaffoldBackgroundColor: AppColors.sand,
      fontFamily: GoogleFonts.cairo().fontFamily,
    );

    return base.copyWith(
      textTheme: GoogleFonts.cairoTextTheme(base.textTheme).copyWith(
        headlineSmall: GoogleFonts.cairo(fontWeight: FontWeight.w800, color: AppColors.ink),
        titleLarge: GoogleFonts.cairo(fontWeight: FontWeight.w700, color: AppColors.ink),
        titleMedium: GoogleFonts.cairo(fontWeight: FontWeight.w600, color: AppColors.ink),
        bodyMedium: GoogleFonts.cairo(color: AppColors.ink),
        bodySmall: GoogleFonts.cairo(color: AppColors.inkSoft, fontSize: 13),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.sand,
        foregroundColor: AppColors.ink,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.cairo(
          fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.ink,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.indigo500,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: GoogleFonts.cairo(fontWeight: FontWeight.w700, fontSize: 16),
          elevation: 0,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.indigo500,
          minimumSize: const Size.fromHeight(52),
          side: const BorderSide(color: AppColors.sandDeep),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: GoogleFonts.cairo(fontWeight: FontWeight.w700, fontSize: 16),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.sandDeep),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.sandDeep),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.indigo500, width: 1.5),
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.sandDim),
        ),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.sandDim, thickness: 1),
    );
  }

  /// نمط الأرقام/المبالغ/أكواد الرحلات — يُستخدم صراحة أينما ظهر رقم مهم
  static TextStyle tabular({double size = 16, FontWeight weight = FontWeight.w600, Color? color}) {
    return GoogleFonts.ibmPlexMono(fontSize: size, fontWeight: weight, color: color ?? AppColors.ink);
  }
}
