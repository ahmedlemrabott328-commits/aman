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

class TripCaptain extends Equatable {
  const TripCaptain({
    required this.id,
    required this.fullName,
    required this.phone,
    this.avatarUrl,
    required this.ratingAvg,
    this.currentLat,
    this.currentLng,
    this.vehiclePlate,
  });

  final int id;
  final String fullName;
  final String phone;
  final String? avatarUrl;
  final double ratingAvg;
  final double? currentLat;
  final double? currentLng;
  final String? vehiclePlate;

  factory TripCaptain.fromJson(Map<String, dynamic> json) {
    final vehicle = json['vehicle'];
    return TripCaptain(
      id: json['id'] as int,
      fullName: json['full_name'] as String,
      phone: json['phone'] as String,
      avatarUrl: json['avatar_url'] as String?,
      ratingAvg: (json['rating_avg'] as num?)?.toDouble() ?? 5.0,
      currentLat: (json['current_lat'] as num?)?.toDouble(),
      currentLng: (json['current_lng'] as num?)?.toDouble(),
      vehiclePlate: vehicle is Map ? vehicle['plate_number'] as String? : null,
    );
  }

  @override
  List<Object?> get props => [id];
}

class Trip extends Equatable {
  const Trip({
    required this.id,
    required this.tripCode,
    required this.status,
    required this.serviceName,
    required this.pickup,
    this.dropoff,
    this.captain,
    this.estimatedPrice,
    this.finalPrice,
    required this.currency,
  });

  final int id;
  final String tripCode;
  final TripStatus status;
  final String serviceName;
  final TripLocation pickup;
  final TripLocation? dropoff;
  final TripCaptain? captain;
  final double? estimatedPrice;
  final double? finalPrice;
  final String currency;

  factory Trip.fromJson(Map<String, dynamic> json) {
    return Trip(
      id: json['id'] as int,
      tripCode: json['trip_code'] as String,
      status: tripStatusFromString(json['status'] as String),
      serviceName: (json['service']?['name'] as String?) ?? '',
      pickup: TripLocation.fromJson(json['pickup'] as Map<String, dynamic>),
      dropoff: json['dropoff'] != null ? TripLocation.fromJson(json['dropoff'] as Map<String, dynamic>) : null,
      captain: json['captain'] != null && (json['captain'] as Map).isNotEmpty
          ? TripCaptain.fromJson(json['captain'] as Map<String, dynamic>)
          : null,
      estimatedPrice: (json['estimated_price'] as num?)?.toDouble(),
      finalPrice: (json['final_price'] as num?)?.toDouble(),
      currency: json['currency'] as String? ?? 'MRU',
    );
  }

  bool get isOngoing => status == TripStatus.requested || status == TripStatus.searching ||
      status == TripStatus.accepted || status == TripStatus.arrived || status == TripStatus.inProgress;

  @override
  List<Object?> get props => [id, status];
}
