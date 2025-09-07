import { db, collections } from '../config/firebase';
import { Booking, BookingFilters, PaginationOptions } from '../types';

export class BookingService {
  /**
   * Create a new booking
   */
  static async createBooking(bookingData: Omit<Booking, 'bookingId' | 'createdAt' | 'updatedAt'>): Promise<Booking> {
    try {
      const bookingRef = db.collection(collections.bookings).doc();
      
      const newBooking: Booking = {
        ...bookingData,
        bookingId: bookingRef.id,
        status: 'pending',
        createdAt: new Date() as any,
        updatedAt: new Date() as any,
      };

      await bookingRef.set(newBooking);
      
      return newBooking;
    } catch (error) {
      console.error('Error creating booking:', error);
      throw new Error('Failed to create booking');
    }
  }

  /**
   * Get booking by ID
   */
  static async getBookingById(bookingId: string): Promise<Booking | null> {
    try {
      const bookingDoc = await db.collection(collections.bookings).doc(bookingId).get();
      
      if (!bookingDoc.exists) {
        return null;
      }

      return { bookingId, ...bookingDoc.data() } as Booking;
    } catch (error) {
      console.error('Error fetching booking:', error);
      throw new Error('Failed to fetch booking');
    }
  }

  /**
   * Get bookings for a specific user
   */
  static async getUserBookings(
    userId: string, 
    options: PaginationOptions = {}
  ): Promise<{ bookings: Booking[]; total: number }> {
    try {
      const { page = 1, limit = 10, sortBy = 'createdAt', sortOrder = 'desc' } = options;
      const offset = (page - 1) * limit;

      let query = db
        .collection(collections.bookings)
        .where('userId', '==', userId)
        .orderBy(sortBy, sortOrder);

      // Get total count
      const totalSnapshot = await db
        .collection(collections.bookings)
        .where('userId', '==', userId)
        .get();
      
      const total = totalSnapshot.size;

      // Get paginated results
      const snapshot = await query.limit(limit).offset(offset).get();

      const bookings = snapshot.docs.map((doc: any) => ({
        bookingId: doc.id,
        ...doc.data()
      })) as Booking[];

      return { bookings, total };
    } catch (error) {
      console.error('Error fetching user bookings:', error);
      throw new Error('Failed to fetch user bookings');
    }
  }

  /**
   * Get all bookings with filters (admin only)
   */
  static async getAllBookings(
    filters: BookingFilters = {},
    options: PaginationOptions = {}
  ): Promise<{ bookings: Booking[]; total: number }> {
    try {
      const { page = 1, limit = 20, sortBy = 'createdAt', sortOrder = 'desc' } = options;
      const offset = (page - 1) * limit;

      let query = db.collection(collections.bookings) as any;

      // Apply filters
      if (filters.status) {
        query = query.where('status', '==', filters.status);
      }
      
      if (filters.hallName) {
        query = query.where('hallName', '==', filters.hallName);
      }
      
      if (filters.department) {
        query = query.where('department', '==', filters.department);
      }
      
      if (filters.userId) {
        query = query.where('userId', '==', filters.userId);
      }

      // Add ordering
      query = query.orderBy(sortBy, sortOrder);

      // Get total count with same filters
      let countQuery = db.collection(collections.bookings) as any;
      if (filters.status) countQuery = countQuery.where('status', '==', filters.status);
      if (filters.hallName) countQuery = countQuery.where('hallName', '==', filters.hallName);
      if (filters.department) countQuery = countQuery.where('department', '==', filters.department);
      if (filters.userId) countQuery = countQuery.where('userId', '==', filters.userId);
      
      const totalSnapshot = await countQuery.get();
      const total = totalSnapshot.size;

      // Get paginated results
      const snapshot = await query.limit(limit).offset(offset).get();

      const bookings = snapshot.docs.map((doc: any) => ({
        bookingId: doc.id,
        ...doc.data()
      })) as Booking[];

      return { bookings, total };
    } catch (error) {
      console.error('Error fetching all bookings:', error);
      throw new Error('Failed to fetch bookings');
    }
  }

  /**
   * Update booking status (admin only)
   */
  static async updateBookingStatus(
    bookingId: string,
    status: 'approved' | 'rejected',
    adminId: string,
    adminComments?: string
  ): Promise<Booking> {
    try {
      const bookingRef = db.collection(collections.bookings).doc(bookingId);
      
      // Get the booking data first
      const bookingDoc = await bookingRef.get();
      const bookingData = bookingDoc.data();
      
      if (!bookingData) {
        throw new Error('Booking not found');
      }
      
      const updateData: any = {
        status,
        updatedAt: new Date(),
      };

      if (status === 'approved') {
        updateData.approvedBy = adminId;
      } else if (status === 'rejected') {
        updateData.rejectedBy = adminId;
      }

      if (adminComments) {
        updateData.adminComments = adminComments;
      }

      await bookingRef.update(updateData);

      // Create notification for the user
      await this.createBookingStatusNotification(
        bookingData.userId,
        status,
        bookingData.hallName,
        bookingData.purpose,
        adminComments
      );

      const updatedDoc = await bookingRef.get();
      return { bookingId, ...updatedDoc.data() } as Booking;
    } catch (error) {
      console.error('Error updating booking status:', error);
      throw new Error('Failed to update booking status');
    }
  }

  /**
   * Create notification when booking status changes
   */
  private static async createBookingStatusNotification(
    userId: string,
    status: 'approved' | 'rejected',
    hallName: string,
    purpose: string,
    adminComments?: string
  ): Promise<void> {
    try {
      const notificationData = {
        userId,
        title: status === 'approved' 
          ? 'Booking Approved! 🎉' 
          : 'Booking Rejected',
        message: status === 'approved'
          ? `Your booking for "${hallName}" (${purpose}) has been approved! You can now proceed with your event.`
          : `Your booking for "${hallName}" (${purpose}) has been rejected.${adminComments ? ` Reason: ${adminComments}` : ''}`,
        type: status === 'approved' ? 'success' : 'error',
        read: false,
        createdAt: new Date()
      };

      await db.collection(collections.notifications).add(notificationData);
    } catch (error) {
      console.error('Error creating notification:', error);
      // Don't throw error here as it shouldn't break the booking update
    }
  }

  /**
   * Delete booking
   */
  static async deleteBooking(bookingId: string): Promise<void> {
    try {
      await db.collection(collections.bookings).doc(bookingId).delete();
    } catch (error) {
      console.error('Error deleting booking:', error);
      throw new Error('Failed to delete booking');
    }
  }

  /**
   * Get pending bookings (admin only)
   */
  static async getPendingBookings(options: PaginationOptions = {}): Promise<{ bookings: Booking[]; total: number }> {
    return this.getAllBookings({ status: 'pending' }, options);
  }

  /**
   * Get approved bookings (admin only)
   */
  static async getApprovedBookings(options: PaginationOptions = {}): Promise<{ bookings: Booking[]; total: number }> {
    return this.getAllBookings({ status: 'approved' }, options);
  }

  /**
   * Get rejected bookings (admin only)
   */
  static async getRejectedBookings(options: PaginationOptions = {}): Promise<{ bookings: Booking[]; total: number }> {
    return this.getAllBookings({ status: 'rejected' }, options);
  }

  /**
   * Check for booking conflicts
   */
  static async checkBookingConflicts(
    hallName: string,
    dates: string[],
    timeFrom: string,
    timeTo: string,
    excludeBookingId?: string
  ): Promise<Booking[]> {
    try {
      let query = db
        .collection(collections.bookings)
        .where('hallName', '==', hallName)
        .where('status', 'in', ['pending', 'approved']);

      const snapshot = await query.get();
      
      const conflicts = snapshot.docs
        .map(doc => ({ bookingId: doc.id, ...doc.data() } as Booking))
        .filter(booking => {
          // Exclude current booking if updating
          if (excludeBookingId && booking.bookingId === excludeBookingId) {
            return false;
          }

          // Check for date conflicts
          const hasDateConflict = booking.dates.some(bookingDate => 
            dates.includes(bookingDate)
          );

          if (!hasDateConflict) {
            return false;
          }

          // Check for time conflicts
          const bookingStart = this.timeToMinutes(booking.timeFrom);
          const bookingEnd = this.timeToMinutes(booking.timeTo);
          const newStart = this.timeToMinutes(timeFrom);
          const newEnd = this.timeToMinutes(timeTo);

          return (newStart < bookingEnd && newEnd > bookingStart);
        });

      return conflicts;
    } catch (error) {
      console.error('Error checking booking conflicts:', error);
      throw new Error('Failed to check booking conflicts');
    }
  }

  /**
   * Convert time string to minutes for comparison
   */
  private static timeToMinutes(time: string): number {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
  }

  /**
   * Check if a hall is available on a specific date
   */
  static async checkHallAvailability(
    hallName: string,
    date: string
  ): Promise<{ available: boolean; reason?: string }> {
    try {
      // Validate date is within 1 month
      const selectedDate = new Date(date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const maxDate = new Date();
      maxDate.setDate(today.getDate() + 30);
      maxDate.setHours(23, 59, 59, 999);

      if (selectedDate < today) {
        return { available: false, reason: 'Cannot book for past dates' };
      }

      if (selectedDate > maxDate) {
        return { available: false, reason: 'Bookings allowed only within 1 month from today' };
      }

      // Check for existing bookings
      const snapshot = await db
        .collection(collections.bookings)
        .where('hallName', '==', hallName)
        .where('dates', 'array-contains', date)
        .where('status', 'in', ['pending', 'approved'])
        .get();

      if (!snapshot.empty) {
        return { available: false, reason: 'Hall is already booked on this date' };
      }

      return { available: true };
    } catch (error) {
      console.error('Error checking hall availability:', error);
      throw new Error('Failed to check hall availability');
    }
  }

  /**
   * Get booking statistics (admin only)
   */
  static async getBookingStats(): Promise<{
    total: number;
    pending: number;
    approved: number;
    rejected: number;
    thisMonth: number;
  }> {
    try {
      const [totalSnapshot, pendingSnapshot, approvedSnapshot, rejectedSnapshot] = await Promise.all([
        db.collection(collections.bookings).get(),
        db.collection(collections.bookings).where('status', '==', 'pending').get(),
        db.collection(collections.bookings).where('status', '==', 'approved').get(),
        db.collection(collections.bookings).where('status', '==', 'rejected').get(),
      ]);

      // Get this month's bookings
      const startOfMonth = new Date();
      startOfMonth.setDate(1);
      startOfMonth.setHours(0, 0, 0, 0);

      const thisMonthSnapshot = await db
        .collection(collections.bookings)
        .where('createdAt', '>=', startOfMonth)
        .get();

      return {
        total: totalSnapshot.size,
        pending: pendingSnapshot.size,
        approved: approvedSnapshot.size,
        rejected: rejectedSnapshot.size,
        thisMonth: thisMonthSnapshot.size,
      };
    } catch (error) {
      console.error('Error fetching booking stats:', error);
      throw new Error('Failed to fetch booking statistics');
    }
  }
}