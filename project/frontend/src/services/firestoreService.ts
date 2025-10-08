import { 
  collection, 
  doc, 
  getDocs, 
  getDoc, 
  addDoc, 
  updateDoc, 
  setDoc,
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
      
      // Check if user document exists first
      const userSnap = await getDoc(userRef);
      
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
      
      if (userSnap.exists()) {
        // Update existing document
        await updateDoc(userRef, updateData);
        console.log('✅ Updated existing user profile');
      } else {
        // Create new document if it doesn't exist
        const newUserData = {
          uid: userId,
          role: 'user', // Default role
          createdAt: new Date().toISOString(),
          ...updateData
        };
        await setDoc(userRef, newUserData);
        console.log('✅ Created new user profile document');
      }
    } catch (error: any) {
      console.error('Error saving user profile:', error);
      console.error('Error details:', {
        code: error.code,
        message: error.message,
        userId: userId
      });
      throw new Error(`Failed to save user profile: ${error.message || 'Unknown error'}`);
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


  // Utility function to find users by email
  static async findUsersByEmail(email: string): Promise<any[]> {
    try {
      const usersRef = collection(db, collections.users);
      const q = query(usersRef, where('email', '==', email));
      const querySnapshot = await getDocs(q);
      
      return querySnapshot.docs.map(doc => ({
        id: doc.id,
        ...doc.data()
      }));
    } catch (error) {
      console.error('Error finding users by email:', error);
      throw new Error('Failed to find users by email');
    }
  }

  // Utility function to merge duplicate accounts
  static async mergeDuplicateAccounts(email: string): Promise<void> {
    try {
      const duplicateUsers = await this.findUsersByEmail(email);
      
      if (duplicateUsers.length <= 1) {
        console.log('No duplicate accounts found for email:', email);
        return;
      }

      console.log(`Found ${duplicateUsers.length} duplicate accounts for email:`, email);
      
      // Sort by creation date (keep the oldest one)
      duplicateUsers.sort((a, b) => {
        const dateA = a.createdAt?.toDate ? a.createdAt.toDate() : new Date(a.createdAt);
        const dateB = b.createdAt?.toDate ? b.createdAt.toDate() : new Date(b.createdAt);
        return dateA.getTime() - dateB.getTime();
      });

      const primaryUser = duplicateUsers[0];
      const duplicateUsersToDelete = duplicateUsers.slice(1);

      console.log('Primary user (keeping):', primaryUser.id);
      console.log('Duplicate users (deleting):', duplicateUsersToDelete.map(u => u.id));

      // Delete duplicate user documents
      for (const duplicateUser of duplicateUsersToDelete) {
        await deleteDoc(doc(db, collections.users, duplicateUser.id));
        console.log('Deleted duplicate user document:', duplicateUser.id);
      }

      console.log('✅ Successfully merged duplicate accounts for email:', email);
    } catch (error) {
      console.error('Error merging duplicate accounts:', error);
      throw new Error('Failed to merge duplicate accounts');
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

  // Check hall availability for a specific date and time range
  static async checkHallAvailability(hallName: string, date: string, timeFrom: string, timeTo: string): Promise<{
    available: boolean;
    reason?: string;
  }> {
    try {
      // Basic validation
      if (!hallName || !date || !timeFrom || !timeTo) {
        return { available: false, reason: 'Missing date or time.' };
      }

      // Validate date within 1 month window as before
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

      // Fetch bookings on the same date for this hall with pending/approved status
      const q = query(
        collection(db, collections.bookings),
        where('hallName', '==', hallName),
        where('dates', 'array-contains', date),
        where('status', 'in', ['pending', 'approved'])
      );
      
      const snapshot = await getDocs(q);

      // Convert HH:MM to minutes helper
      const toMinutes = (t: string) => {
        const [h, m] = t.split(':').map(Number);
        return h * 60 + m;
      };

      const newStart = toMinutes(timeFrom);
      const newEnd = toMinutes(timeTo);

      if (newEnd <= newStart) {
        return { available: false, reason: 'End time must be after start time' };
      }

      // Check overlaps with existing bookings
      const hasOverlap = snapshot.docs.some((docSnap: any) => {
        const b = docSnap.data() as Booking;
        const existingStart = toMinutes(b.timeFrom);
        const existingEnd = toMinutes(b.timeTo);
        return newStart < existingEnd && newEnd > existingStart;
      });

      if (hasOverlap) {
        return { available: false, reason: 'Selected time overlaps with another booking' };
      }

      return { available: true };
    } catch (error) {
      console.error('Error checking hall availability:', error);
      return { available: false, reason: 'Error checking availability. Please try again.' };
    }
  }

  // Debug function to check all bookings for a hall
  static async debugHallBookings(hallName: string): Promise<void> {
    try {
      console.log(`🔍 Debug: Checking all bookings for hall: "${hallName}"`);
      
      // First, let's get ALL bookings to see what hall names exist
      const allBookingsQuery = query(collection(db, collections.bookings));
      const allSnapshot = await getDocs(allBookingsQuery);
      console.log(`📋 Total bookings in database: ${allSnapshot.docs.length}`);
      
      // Show all unique hall names
      const hallNames = new Set();
      allSnapshot.docs.forEach(doc => {
        const booking = doc.data() as Booking;
        if (booking.hallName) {
          hallNames.add(booking.hallName);
        }
      });
      console.log(`📋 All hall names in database:`, Array.from(hallNames));
      
      // Now check for exact match
      const q = query(
        collection(db, collections.bookings),
        where('hallName', '==', hallName)
      );
      const snapshot = await getDocs(q);
      console.log(`📋 Exact matches for "${hallName}": ${snapshot.docs.length}`);
      
      snapshot.docs.forEach((doc, index) => {
        const booking = doc.data() as Booking;
        console.log(`📋 Booking ${index + 1}:`, {
          id: doc.id,
          status: booking.status,
          hallName: `"${booking.hallName}"`,
          dates: booking.dates,
          timeFrom: booking.timeFrom,
          timeTo: booking.timeTo,
          purpose: booking.purpose
        });
      });
    } catch (error) {
      console.error('Debug error:', error);
    }
  }

  // BULLETPROOF availability check - simplified and guaranteed to work
  static async checkHallAvailabilityRange(
    hallName: string,
    fromDate: string,
    fromTime: string,
    toDate: string,
    toTime: string
  ): Promise<{ available: boolean; reason?: string }> {
    try {
      console.log('🚀 BULLETPROOF CHECK STARTING...');
      console.log('🔍 Parameters:', { hallName, fromDate, fromTime, toDate, toTime });
      
      if (!hallName || !fromDate || !fromTime || !toDate || !toTime) {
        console.log('❌ Missing required fields');
        return { available: false, reason: 'Missing date or time.' };
      }

      // Build date list inclusive
      const start = new Date(fromDate);
      const end = new Date(toDate);
      const dates: string[] = [];
      for (let d = new Date(start); d.getTime() <= end.getTime(); d.setDate(d.getDate() + 1)) {
        dates.push(d.toISOString().split('T')[0]);
      }
      console.log(`📅 Dates to check: ${dates.join(', ')}`);

      // Get ALL bookings - no filtering, no complex queries
      console.log('🔍 Getting ALL bookings from database...');
      const allBookingsQuery = query(collection(db, collections.bookings));
      const allSnapshot = await getDocs(allBookingsQuery);
      console.log(`📋 Total bookings found: ${allSnapshot.docs.length}`);

      // Show every single booking
      allSnapshot.docs.forEach((doc, index) => {
        const booking = doc.data() as Booking;
        console.log(`📋 Booking ${index + 1}:`, {
          id: doc.id,
          hallName: `"${booking.hallName}"`,
          status: booking.status,
          dates: booking.dates,
          timeFrom: booking.timeFrom,
          timeTo: booking.timeTo
        });
      });

      // Convert time to minutes for comparison
      const toMinutes = (time: string) => {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
      };

      const requestedStartTime = toMinutes(fromTime);
      const requestedEndTime = toMinutes(toTime);

      console.log(`⏰ Requested time: ${fromTime} (${requestedStartTime} min) to ${toTime} (${requestedEndTime} min)`);

      // Check each date in our range
      for (const date of dates) {
        console.log(`🔍 Checking date: ${date}`);
        
        // Find bookings for this hall on this date
        const bookingsOnThisDate = allSnapshot.docs.filter(doc => {
          const booking = doc.data() as Booking;
          const isOurHall = booking.hallName === hallName;
          const hasThisDate = booking.dates && booking.dates.includes(date);
          const isPendingOrApproved = booking.status === 'pending' || booking.status === 'approved';
          
          console.log(`📋 Checking booking: hall="${booking.hallName}" vs "${hallName}", date=${booking.dates} includes ${date}, status=${booking.status}`);
          
          return isOurHall && hasThisDate && isPendingOrApproved;
        });

        console.log(`📋 Found ${bookingsOnThisDate.length} bookings for ${date}`);

        // Check for time conflicts with each existing booking
        for (const doc of bookingsOnThisDate) {
          const booking = doc.data() as Booking;
          const existingStartTime = toMinutes(booking.timeFrom);
          const existingEndTime = toMinutes(booking.timeTo);

          console.log(`⏰ Existing booking: ${booking.timeFrom} (${existingStartTime} min) to ${booking.timeTo} (${existingEndTime} min)`);

          // Check for time overlap
          // Two time ranges overlap if: start1 < end2 AND start2 < end1
          const hasOverlap = requestedStartTime < existingEndTime && existingStartTime < requestedEndTime;

          console.log(`🔍 Time overlap check: ${requestedStartTime} < ${existingEndTime} AND ${existingStartTime} < ${requestedEndTime} = ${hasOverlap}`);

          if (hasOverlap) {
            console.log(`🚫🚫🚫 TIME CONFLICT! Overlaps with existing booking:`, {
              status: booking.status,
              time: `${booking.timeFrom}-${booking.timeTo}`,
              hallName: booking.hallName
            });
            
            return { 
              available: false, 
              reason: `❌ Hall is NOT available on ${date} from ${fromTime} to ${toTime} due to time conflict with existing ${booking.status} booking (${booking.timeFrom} - ${booking.timeTo})` 
            };
          } else {
            console.log(`✅ No time conflict with booking ${booking.timeFrom}-${booking.timeTo}`);
          }
        }
      }

      console.log('✅ No conflicts found - hall is available');
      return { available: true };
        
    } catch (e) {
      console.error('❌ Error in bulletproof check:', e);
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
