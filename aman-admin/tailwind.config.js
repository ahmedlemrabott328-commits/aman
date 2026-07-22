/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        // نظام ألوان AMAN — مستوحى من صبغة النيلة الموريتانية التقليدية ورمال الصحراء
        ink: {
          DEFAULT: '#1A2340',   // نص أساسي / أسطح داكنة
          soft: '#5B6178',      // نص ثانوي
        },
        indigo: {
          50:  '#EEF0F8',
          100: '#D6DAF0',
          200: '#AEB6E0',
          300: '#8590CC',
          400: '#5C6AB4',
          500: '#2E3A6E',        // اللون الأساسي للعلامة
          600: '#25305C',
          700: '#1D264A',
          800: '#161C38',
          900: '#0F1326',
        },
        sand: {
          DEFAULT: '#F6F1E7',    // خلفية التطبيق
          dim: '#ECE4D3',        // خلفيات البطاقات/الحدود
          deep: '#DCCFAE',
        },
        gold: {
          DEFAULT: '#C1922E',    // لون التمييز (أزرار رئيسية، تنبيهات)
          light: '#E0B863',
          dark: '#96701F',
        },
        teal: {
          DEFAULT: '#2F6F62',    // نجاح / اعتماد
          light: '#E3EFEC',
        },
        terracotta: {
          DEFAULT: '#B5533C',    // خطر / رفض
          light: '#F5E6E1',
        },
      },
      fontFamily: {
        sans: ['"Cairo"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(26,35,64,0.06), 0 1px 8px rgba(26,35,64,0.04)',
      },
      borderRadius: {
        xl: '14px',
      },
    },
  },
  plugins: [],
};
