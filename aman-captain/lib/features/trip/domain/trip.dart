import 'package:equatable/equatable.dart';

enum TripStatus {
  requested, searching, accepted, arrived, inProgress, completed, cancelled, noCaptainFound,
}

TripStatus tripStatusFromString(String value) {
  switch (value) {
    case 'requested': return TripStatus.requested;
    case 'searching': return TripStatus.searching;
    case 'accepted': return TripStatus.accepted;
    case 'arrived': return TripStatus.arrived;
    case 'in_progress': return TripStatus.inProgress;
    case 'completed': return TripStatus.completed;
    case 'cancelled': return TripStatus.cancelled;
    case 'no_captain_found': return TripStatus.noCaptainFound;
    default: return TripStatus.requested;
  }
}

class TripCustomer extends Equatable {
  const TripCustomer({required this.id, this.fullName, required this.phone});

  final int id;
  final String? fullName;
  final String phone;

  factory TripCustomer.fromJson(Map<String, dynamic> json) => TripCustomer(
        id: json['id'] as int,
        fullName: json['full_name'] as String?,
        phone: json['phone'] as String,
      );

  @override
  List<Object?> get props => [id, fullName, phone];
}

class TripLocation extends Equatable {
  const TripLocation({required this.address, required this.lat, required this.lng});

  final String address;
  final double lat;
  final double lng;

  factory TripLocation.fromJson(Map<String, dynamic> json) => TripLocation(
        address: json['address'] as String,
        lat: (json['lat'] as num).toDouble(),
        lng: (json['lng'] as num).toDouble(),
      );

  @override
  List<Object?> get props => [address, lat, lng];
}

class Trip extends Equatable {
  const Trip({
    required this.id,
    required this.tripCode,
    required this.status,
    required this.serviceName,
    required this.pickup,
    this.dropoff,
    this.customer,
    this.estimatedPrice,
    this.finalPrice,
    required this.currency,
    this.distanceKm,
    this.durationMin,
  });

  final int id;
  final String tripCode;
  final TripStatus status;
  final String serviceName;
  final TripLocation pickup;
  final TripLocation? dropoff;
  final TripCustomer? customer;
  final double? estimatedPrice;
  final double? finalPrice;
  final String currency;
  final double? distanceKm;
  final int? durationMin;

  factory Trip.fromJson(Map<String, dynamic> json) {
    return Trip(
      id: json['id'] as int,
      tripCode: json['trip_code'] as String,
      status: tripStatusFromString(json['status'] as String),
      serviceName: (json['service']?['name'] as String?) ?? '',
      pickup: TripLocation.fromJson(json['pickup'] as Map<String, dynamic>),
      dropoff: json['dropoff'] != null ? TripLocation.fromJson(json['dropoff'] as Map<String, dynamic>) : null,
      customer: json['customer'] != null && (json['customer'] as Map).isNotEmpty
          ? TripCustomer.fromJson(json['customer'] as Map<String, dynamic>)
          : null,
      estimatedPrice: (json['estimated_price'] as num?)?.toDouble(),
      finalPrice: (json['final_price'] as num?)?.toDouble(),
      currency: json['currency'] as String? ?? 'MRU',
      distanceKm: (json['distance_km'] as num?)?.toDouble(),
      durationMin: json['duration_min'] as int?,
    );
  }

  bool get isActive => status == TripStatus.accepted || status == TripStatus.arrived || status == TripStatus.inProgress;

  @override
  List<Object?> get props => [id, status, tripCode];
}

/// عرض رحلة جديد يصل عبر البث اللحظي (Reverb) — راجع NewTripOffer في aman-backend
class TripOffer extends Equatable {
  const TripOffer({
    required this.tripId,
    required this.pickupAddress,
    required this.pickupLat,
    required this.pickupLng,
    required this.estimatedPrice,
    required this.currency,
    required this.offerTimeoutSeconds,
  });

  final int tripId;
  final String pickupAddress;
  final double pickupLat;
  final double pickupLng;
  final double estimatedPrice;
  final String currency;
  final int offerTimeoutSeconds;

  factory TripOffer.fromJson(Map<String, dynamic> json) => TripOffer(
        tripId: json['trip_id'] as int,
        pickupAddress: json['pickup_address'] as String,
        pickupLat: (json['pickup_lat'] as num).toDouble(),
        pickupLng: (json['pickup_lng'] as num).toDouble(),
        estimatedPrice: (json['estimated_price'] as num).toDouble(),
        currency: json['currency'] as String,
        offerTimeoutSeconds: json['offer_timeout_seconds'] as int? ?? 15,
      );

  @override
  List<Object?> get props => [tripId];
}
