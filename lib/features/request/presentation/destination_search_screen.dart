import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geocoding/geocoding.dart';
import '../../../core/theme/app_colors.dart';
import 'request_controller.dart';

class DestinationSearchScreen extends ConsumerStatefulWidget {
  const DestinationSearchScreen({super.key});

  @override
  ConsumerState<DestinationSearchScreen> createState() => _DestinationSearchScreenState();
}

class _DestinationSearchScreenState extends ConsumerState<DestinationSearchScreen> {
  final _controller = TextEditingController();
  List<Location> _results = [];
  bool _searching = false;
  String? _error;

  Future<void> _search(String query) async {
    if (query.trim().length < 3) {
      setState(() => _results = []);
      return;
    }
    setState(() { _searching = true; _error = null; });
    try {
      // نُلحق "موريتانيا" لتضييق نتائج البحث الجغرافي على السياق المحلي
      final results = await locationFromAddress('$query، موريتانيا');
      setState(() => _results = results);
    } catch (_) {
      setState(() => _error = 'تعذّر العثور على هذا العنوان، جرّب صياغة أخرى');
    } finally {
      setState(() => _searching = false);
    }
  }

  Future<void> _select(Location location) async {
    String address = _controller.text;
    try {
      final placemarks = await placemarkFromCoordinates(location.latitude, location.longitude);
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        address = [p.street, p.subLocality, p.locality].where((e) => e != null && e.isNotEmpty).join('، ');
        if (address.isEmpty) address = _controller.text;
      }
    } catch (_) {
      // نكتفي بالنص الذي أدخله الزبون إن فشل الحصول على عنوان مقروء
    }

    if (!mounted) return;
    await ref.read(requestControllerProvider.notifier).setDropoff(
          address, LatLngPoint(location.latitude, location.longitude),
        );
    if (mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('إلى أين تريد الذهاب؟')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _controller,
              autofocus: true,
              onChanged: _search,
              decoration: InputDecoration(
                hintText: 'اكتب اسم المكان أو الحي...',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _searching
                    ? const Padding(padding: EdgeInsets.all(12), child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)))
                    : null,
              ),
            ),
          ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(_error!, style: const TextStyle(color: AppColors.terracotta)),
            ),
          Expanded(
            child: ListView.builder(
              itemCount: _results.length,
              itemBuilder: (context, index) {
                final loc = _results[index];
                return ListTile(
                  leading: const Icon(Icons.location_on_outlined, color: AppColors.indigo500),
                  title: Text('${_controller.text} #${index + 1}'),
                  subtitle: Text('${loc.latitude.toStringAsFixed(4)}, ${loc.longitude.toStringAsFixed(4)}'),
                  onTap: () => _select(loc),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
