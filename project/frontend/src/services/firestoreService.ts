import { 
  collection, 
  doc, 
  getDocs, 
  getDoc, 
  addDoc, 
  updateDoc, 
  deleteDoc, 
  query, 
  where, 
  orderBy, 
  limit,
  onSnapshot 
} from 'firebase/firestore';
import { db, collections } from '../config/firebase';
import { Hall, Booking, Notification, User } from '../types';

export class FirestoreService {
  // Halls
  static async getHalls(): Promise<Hall[]> {
    try {
      const hallsSnapshot = await getDocs(collection(db, collections.halls));
      return hallsSnapshot.docs.map(doc => ({
        id: doc.id,
        ...doc.data()
      })) as Hall[];
    } catch (error) {
      console.error('Error fetching halls:', error);
      throw new Error('Failed to fetch halls');
    }
  }

  static async getHallById(hallId: string): Promise<Hall | null> {
    try {
      const hallDoc = await getDoc(doc(db, collections.halls, hallId));
      if (hallDoc.exists()) {
        return { id: hallDoc.id, ...hallDoc.data() } as Hall;
      }
      return null;
    } catch (error) {
      console.error('Error fetching hall:', error);
      throw new Error('Failed to fetch hall');
    }
  }

  // Submit a cancellation review to backend API (ensures auth and server-side validation)
  static async requestBookingCancellation(bookingId: string, reason: string, idToken: string): Promise<void> {
    const resp = await fetch(`/api/bookings/${bookingId}/request-cancel`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${idToken}`
      },
      body: JSON.stringify({ reason })
    });
    if (!resp.ok) {
      const text = await resp.text();
      throw new Error(text || 'Failed to submit cancellation');
    }
  }

  // Bookings
  static async getUserBookings(userId: string): Promise<Booking[]> {
    try {
      // First get all bookings for the user without ordering
      const q = query(
        collection(db, collections.bookings),
        where('userId', '==', userId)
      );
      const bookingsSnapshot = await getDocs(q);
      
      // Sort in JavaScript instead of Firestore to avoid index requirement
      const bookings = bookingsSnapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: doc.id,
          bookingId: doc.id,
          ...data,
          // Map backend fields to frontend fields for compatibility
          facilitiesRequired: data.facilities || data.facilitiesRequired || [],
          userContact: data.userMobile || data.userContact || '',
        } as Booking;
      });

      // Sort by createdAt in descending order (newest first)
      return bookings.sort((a, b) => {
        const dateA = a.createdAt?.toDate ? a.createdAt.toDate() : new Date(a.createdAt);
        const dateB = b.createdAt?.toDate ? b.createdAt.toDate() : new Date(b.createdAt);
        return dateB.getTime() - dateA.getTime();
      });
    } catch (error) {
      console.error('Error fetching user bookings:', error);
      throw new Error('Failed to fetch bookings');
    }
  }

  static async getBookingById(bookingId: string): Promise<Booking | null> {
    try {
      const bookingDoc = await getDoc(doc(db, collections.bookings, bookingId));
      if (bookingDoc.exists()) {
        return { id: bookingDoc.id, ...bookingDoc.data() } as Booking;
      }
      return null;
    } catch (error) {
      console.error('Error fetching booking:', error);
      throw new Error('Failed to fetch booking');
    }
  }

  static async createBooking(bookingData: Omit<Booking, 'id'>): Promise<Booking> {
    try {
      // Firestore does not accept undefined values. Sanitize payload.
      const payload: any = {};
      Object.entries(bookingData as Record<string, any>).forEach(([key, value]) => {
        if (value !== undefined) {
          payload[key] = value;
        }
      });

      // If single-day data was provided as checkInDate, ensure dates array exists for queries
      if (!payload.dates && payload.checkInDate) {
        payload.dates = [payload.checkInDate];
      }

      payload.createdAt = new Date().toISOString();
      payload.updatedAt = new Date().toISOString();

      const docRef = await addDoc(collection(db, collections.bookings), payload);
      
      return {
        id: docRef.id,
        ...payload
      };
    } catch (error) {
      console.error('Error creating booking:', error);
      throw new Error('Failed to create booking');
    }
  }

  static async updateBooking(bookingId: string, updates: Partial<Booking>): Promise<void> {
    try {
      await updateDoc(doc(db, collections.bookings, bookingId), {
        ...updates,
        updatedAt: new Date().toISOString()
      });
    } catch (error) {
      console.error('Error updating booking:', error);
      throw new Error('Failed to update booking');
    }
  }

  static async deleteBooking(bookingId: string): Promise<void> {
    try {
      await deleteDoc(doc(db, collections.bookings, bookingId));
    } catch (error) {
      console.error('Error deleting booking:', error);
      throw new Error('Failed to delete booking');
    }
  }

  // Notifications
  static async getUserNotifications(userId: string): Promise<Notification[]> {
    try {
      // Avoid composite index requirement by fetching without orderBy
      const q = query(
        collection(db, collections.notifications),
        where('userId', '==', userId)
      );
      const notificationsSnapshot = await getDocs(q);
      const items = notificationsSnapshot.docs.map(doc => ({
        id: doc.id,
        ...doc.data()
      })) as Notification[];
      // Sort locally by createdAt desc
      return items.sort((a: any, b: any) => {
        const aDate = a.createdAt?.toDate ? a.createdAt.toDate() : new Date(a.createdAt);
        const bDate = b.createdAt?.toDate ? b.createdAt.toDate() : new Date(b.createdAt);
        return bDate.getTime() - aDate.getTime();
      });
    } catch (error) {
      console.error('Error fetching notifications:', error);
      return [];
    }
  }

  static async createNotification(notificationData: Omit<Notification, 'id'>): Promise<Notification> {
    try {
      const docRef = await addDoc(collection(db, collections.notifications), {
        ...notificationData,
        createdAt: new Date().toISOString()
      });
      
      return {
        id: docRef.id,
        ...notificationData,
        createdAt: new Date().toISOString()
      };
    } catch (error) {
      console.error('Error creating notification:', error);
      throw new Error('Failed to create notification');
    }
  }

  static async markNotificationAsRead(notificationId: string): Promise<void> {
    try {
      await updateDoc(doc(db, collections.notifications, notificationId), {
        read: true
      });
    } catch (error) {
      console.error('Error marking notification as read:', error);
      throw new Error('Failed to mark notification as read');
    }
  }

  // User Profile methods - store in users collection
  static async saveUserProfile(userId: string, profileData: any): Promise<void> {
    try {
      const userRef = doc(db, collections.users, userId);
      
      // Only include fields that have actual values (not empty strings)
      const updateData: any = {
        updatedAt: new Date().toISOString()
      };
      
      if (profileData.name && profileData.name.trim()) updateData.name = profileData.name.trim();
      if (profileData.email && profileData.email.trim()) updateData.email = profileData.email.trim();
      if (profileData.mobile && profileData.mobile.trim()) updateData.mobile = profileData.mobile.trim();
      if (profileData.designation && profileData.designation.trim()) updateData.designation = profileData.designation.trim();
      if (profileData.department && profileData.department.trim()) updateData.department = profileData.department.trim();
      if (profileData.cabinNumber && profileData.cabinNumber.trim()) updateData.cabinNumber = profileData.cabinNumber.trim();
      if (profileData.officeLocation && profileData.officeLocation.trim()) updateData.officeLocation = profileData.officeLocation.trim();
      
      await updateDoc(userRef, updateData);
    } catch (error) {
      console.error('Error saving user profile:', error);
      throw new Error('Failed to save user profile');
    }
  }

  static async getUserProfile(userId: string): Promise<any> {
    try {
      const userRef = doc(db, collections.users, userId);
      const userSnap = await getDoc(userRef);
      
      if (userSnap.exists()) {
        return userSnap.data();
      } else {
        return null;
      }
    } catch (error) {
      console.error('Error fetching user profile:', error);
      throw new Error('Failed to fetch user profile');
    }
  }

  // Real-time listeners
  static subscribeToUserBookings(userId: string, callback: (bookings: Booking[]) => void) {
    const q = query(
      collection(db, collections.bookings),
      where('userId', '==', userId)
    );
    
    return onSnapshot(q, (snapshot) => {
      const bookings = snapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: doc.id,
          bookingId: doc.id,
          ...data,
          // Map backend fields to frontend fields for compatibility
          facilitiesRequired: data.facilities || data.facilitiesRequired || [],
          userContact: data.userMobile || data.userContact || '',
        } as Booking;
      });

      // Sort by createdAt in descending order (newest first)
      const sortedBookings = bookings.sort((a, b) => {
        const dateA = a.createdAt?.toDate ? a.createdAt.toDate() : new Date(a.createdAt);
        const dateB = b.createdAt?.toDate ? b.createdAt.toDate() : new Date(b.createdAt);
        return dateB.getTime() - dateA.getTime();
      });

      callback(sortedBookings);
    });
  }

  static subscribeToUserNotifications(userId: string, callback: (notifications: Notification[]) => void) {
    const q = query(
      collection(db, collections.notifications),
      where('userId', '==', userId)
    );
    
    return onSnapshot(q, (snapshot) => {
      const notifications = snapshot.docs.map(doc => ({
        id: doc.id,
        ...doc.data()
      })) as Notification[];
      const sorted = notifications.sort((a: any, b: any) => {
        const aDate = a.createdAt?.toDate ? a.createdAt.toDate() : new Date(a.createdAt);
        const bDate = b.createdAt?.toDate ? b.createdAt.toDate() : new Date(b.createdAt);
        return bDate.getTime() - aDate.getTime();
      });
      callback(sorted);
    });
  }

  // Admin methods
  static async getAllBookings(): Promise<Booking[]> {
    try {
      const q = query(collection(db, collections.bookings));
      const bookingsSnapshot = await getDocs(q);
      
      const bookings = bookingsSnapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: doc.id,
          bookingId: doc.id,
          ...data,
          // Map backend fields to frontend fields for compatibility
          facilitiesRequired: data.facilities || data.facilitiesRequired || [],
          userContact: data.userMobile || data.userContact || '',
        } as Booking;
      });

      // Sort by createdAt in descending order (newest first)
      return bookings.sort((a, b) => {
        const dateA = a.createdAt?.toDate ? a.createdAt.toDate() : new Date(a.createdAt);
        const dateB = b.createdAt?.toDate ? b.createdAt.toDate() : new Date(b.createdAt);
        return dateB.getTime() - dateA.getTime();
      });
    } catch (error) {
      console.error('Error fetching all bookings:', error);
      throw new Error('Failed to fetch bookings');
    }
  }

  static async updateUser(userId: string, updates: Partial<User>): Promise<void> {
    try {
      await updateDoc(doc(db, collections.users, userId), {
        ...updates,
        updatedAt: new Date().toISOString()
      });
    } catch (error) {
      console.error('Error updating user:', error);
      throw new Error('Failed to update user');
    }
  }

  static async getAdminNotifications(): Promise<Notification[]> {
    try {
      const q = query(
        collection(db, collections.notifications),
        where('userId', '==', 'admin'),
        orderBy('createdAt', 'desc')
      );
      const notificationsSnapshot = await getDocs(q);
      return notificationsSnapshot.docs.map(doc => ({
        id: doc.id,
        ...doc.data()
      })) as Notification[];
    } catch (error) {
      console.error('Error fetching admin notifications:', error);
      // Return empty array if no notifications exist
      return [];
    }
  }

  // Check hall availability for a specific date
  static async checkHallAvailability(hallName: string, date: string): Promise<{
    available: boolean;
    reason?: string;
  }> {
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

      // Check for existing bookings on this date
      const q = query(
        collection(db, collections.bookings),
        where('hallName', '==', hallName),
        where('dates', 'array-contains', date),
        where('status', 'in', ['pending', 'approved'])
      );
      
      const snapshot = await getDocs(q);
      
      if (!snapshot.empty) {
        return { available: false, reason: 'Hall is already booked on this date' };
      }

      return { available: true };
    } catch (error) {
      console.error('Error checking hall availability:', error);
      return { available: false, reason: 'Error checking availability. Please try again.' };
    }
  }

  // Create a new hall
  static async createHall(hallData: Omit<Hall, 'id'>): Promise<Hall> {
    try {
      const hallRef = await addDoc(collection(db, collections.halls), {
        ...hallData,
        createdAt: new Date(),
        updatedAt: new Date()
      });
      
      return {
        id: hallRef.id,
        ...hallData
      } as Hall;
    } catch (error) {
      console.error('Error creating hall:', error);
      throw new Error('Failed to create hall');
    }
  }

  // Update a hall
  static async updateHall(hallId: string, hallData: Partial<Hall>): Promise<Hall> {
    try {
      const hallRef = doc(db, collections.halls, hallId);
      await updateDoc(hallRef, {
        ...hallData,
        updatedAt: new Date()
      });
      
      const updatedHall = await this.getHallById(hallId);
      if (!updatedHall) {
        throw new Error('Hall not found after update');
      }
      
      return updatedHall;
    } catch (error) {
      console.error('Error updating hall:', error);
      throw new Error('Failed to update hall');
    }
  }

  // Delete a hall
  static async deleteHall(hallId: string): Promise<void> {
    try {
      const hallRef = doc(db, collections.halls, hallId);
      await deleteDoc(hallRef);
    } catch (error) {
      console.error('Error deleting hall:', error);
      throw new Error('Failed to delete hall');
    }
  }

  // Get all users (Admin only)
  static async getAllUsers(): Promise<User[]> {
    try {
      const usersSnapshot = await getDocs(collection(db, collections.users));
      return usersSnapshot.docs.map(doc => ({
        id: doc.id,
        ...doc.data()
      })) as User[];
    } catch (error) {
      console.error('Error fetching users:', error);
      throw new Error('Failed to fetch users');
    }
  }
}
