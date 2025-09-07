export interface User {
  uid: string;
  name: string;
  mobile: string;
  email: string;
  designation?: string;
  department?: string;
  cabinNumber?: string;
  officeLocation?: string;
  role: 'user' | 'admin';
  createdAt: FirebaseFirestore.Timestamp;
  lastLogin?: FirebaseFirestore.Timestamp;
  updatedAt?: FirebaseFirestore.Timestamp;
}

export interface Booking {
  bookingId?: string;
  userId: string;
  userName: string;
  userEmail: string;
  userMobile: string;
  userDesignation: string;
  userDepartment?: string;
  hallName: string;
  department: string;
  organizingDepartment: string;
  purpose: string;
  seatingCapacity: number;
  facilities: string[];
  dates: string[];
  timeFrom: string;
  timeTo: string;
  // Enhanced fields for multi-day bookings
  checkInDate?: string;
  checkInTime?: string;
  checkOutDate?: string;
  checkOutTime?: string;
  numberOfDays?: number;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: FirebaseFirestore.Timestamp;
  updatedAt: FirebaseFirestore.Timestamp;
  approvedBy?: string;
  rejectedBy?: string;
  adminComments?: string;
}

export interface Hall {
  hallId?: string;
  hallName: string;
  capacity: number;
  facilities: string[];
  active: boolean;
  createdAt: FirebaseFirestore.Timestamp;
  updatedAt: FirebaseFirestore.Timestamp;
}

export interface AuthenticatedRequest extends Request {
  user?: User;
}

export interface BookingFilters {
  status?: 'pending' | 'approved' | 'rejected';
  hallName?: string;
  department?: string;
  dateFrom?: string;
  dateTo?: string;
  userId?: string;
}

export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data?: T;
  error?: string;
}

export interface PaginationOptions {
  page?: number;
  limit?: number;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface AvailabilityResponse {
  available: boolean;
  reason?: string;
}