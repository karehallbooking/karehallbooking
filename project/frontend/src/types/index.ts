export interface User {
  uid: string;
  name: string;
  mobile: string;
  email: string;
  department?: string;
  designation?: string;
  cabinNumber?: string;
  officeLocation?: string;
  role: 'user' | 'admin';
}

export interface Hall {
  id: string;
  name: string;
  capacity: number;
  facilities: string[];
  image?: string;
  available: boolean;
  createdAt?: string | any;
  updatedAt?: string | any;
}

export interface Booking {
  id?: string;
  bookingId?: string;
  userId: string;
  userName: string;
  userEmail: string;
  userMobile: string;
  userDesignation: string;
  userDepartment?: string;
  department: string;
  hallId?: string;
  hallName: string;
  organizingDepartment: string;
  purpose: string;
  seatingCapacity: number;
  facilities: string[];
  facilitiesRequired?: string[]; // For backward compatibility
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
  createdAt: string | any;
  updatedAt: string | any;
  approvedBy?: string;
  rejectedBy?: string;
  adminComments?: string;
  rejectionReason?: string;
  cancellationRejectionReason?: string;
  // PDF fields
  eventBrochureLink?: string;
  approvalLetterLink?: string;
}

export interface Notification {
  id: string;
  userId: string;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'error';
  read: boolean;
  createdAt: string;
}