// أنواع مطابقة تمامًا لبنية استجابات AMAN API (راجع aman-backend/app/Http/Resources)

export interface ApiEnvelope<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface PaginatedData<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export type ApprovalStatus = 'pending' | 'approved' | 'rejected' | 'suspended';
export type CustomerStatus = 'active' | 'blocked';
export type TripStatus =
  | 'requested' | 'searching' | 'accepted' | 'arrived'
  | 'in_progress' | 'completed' | 'cancelled' | 'no_captain_found';

export interface City {
  id: number;
  name_ar: string;
  name_fr: string;
  name_en: string;
  center_lat: number | null;
  center_lng: number | null;
  is_active: boolean;
}

export interface Customer {
  id: number;
  full_name: string | null;
  phone: string;
  email: string | null;
  status: CustomerStatus;
  rating_avg: number;
  rating_count: number;
  trips_count?: number;
  created_at: string;
  last_login_at: string | null;
}

export interface CaptainDocument {
  id: number;
  document_type: string;
  file_url: string;
  status: 'pending' | 'approved' | 'rejected';
  rejection_reason: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface Vehicle {
  id: number;
  plate_number: string;
  brand: string | null;
  model: string | null;
  year: number | null;
  color: string | null;
  status: string;
  vehicle_type?: string;
}

export interface Captain {
  id: number;
  full_name: string;
  phone: string;
  email: string | null;
  national_id: string | null;
  city?: { id: number; name: string };
  approval_status: ApprovalStatus;
  rejection_reason: string | null;
  is_online: boolean;
  rating_avg: number;
  rating_count: number;
  documents?: CaptainDocument[];
  vehicles?: Vehicle[];
  services?: string[];
  created_at: string;
  approved_at: string | null;
}

export interface Trip {
  id: number;
  trip_code: string;
  status: TripStatus;
  trip_mode: 'instant' | 'scheduled' | 'open';
  service?: string;
  city?: string;
  customer?: { id: number; full_name: string | null; phone: string };
  captain?: { id: number; full_name: string; phone: string; rating_avg: number } | null;
  pickup_address: string;
  dropoff_address: string | null;
  distance_km: number | null;
  duration_min: number | null;
  estimated_price: number | null;
  final_price: number | null;
  commission_amount: number | null;
  currency: string;
  cancelled_by_type: string | null;
  cancel_reason: string | null;
  requested_at: string;
  completed_at: string | null;
  cancelled_at: string | null;
}

export interface PricingRule {
  id: number;
  service_id: number;
  city_id: number;
  vehicle_type_id: number | null;
  base_fare: number;
  price_per_km: number;
  price_per_minute: number;
  min_fare: number;
  cancellation_fee: number;
  currency: string;
  is_active: boolean;
  effective_from: string;
}

export interface CommissionRule {
  id: number;
  service_id: number;
  city_id: number | null;
  commission_type: 'percentage' | 'fixed';
  value: number;
  is_active: boolean;
  effective_from: string;
}

export interface Wallet {
  id: number;
  captain: { id: number; full_name: string; phone: string };
  balance: number;
  currency: string;
  updated_at: string;
}

export interface WalletTransaction {
  id: number;
  trip_id: number | null;
  type: string;
  amount: number;
  balance_after: number;
  description: string | null;
  created_at: string;
}

export interface DashboardStats {
  customers_total: number;
  captains_total: number;
  captains_by_status: Record<ApprovalStatus, number>;
  captains_online_now: number;
  trips_total: number;
  trips_completed: number;
  trips_cancelled: number;
  revenue: { gross: number; commission: number };
  trips_by_service: Record<string, number>;
  trips_by_day: Record<string, number>;
}

export interface AdminProfile {
  id: number;
  full_name: string;
  email: string;
  roles: string[];
  permissions: string[];
}
