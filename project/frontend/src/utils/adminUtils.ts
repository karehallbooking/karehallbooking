import { doc, updateDoc, getDoc } from 'firebase/firestore';
import { db, collections } from '../config/firebase';

/**
 * Update an existing user to admin role
 */
export async function updateUserToAdmin(userId: string): Promise<void> {
  try {
    await updateDoc(doc(db, collections.users, userId), {
      role: 'admin',
      updatedAt: new Date()
    });
    
    console.log('✅ User updated to admin role successfully!');
  } catch (error) {
    console.error('❌ Error updating user to admin:', error);
    throw error;
  }
}

/**
 * Check if a user is admin by email
 */
export function isAdminEmail(email: string): boolean {
  return email === 'karehallbooking@gmail.com';
}

/**
 * Get admin user data (deprecated - use actual user data from Firestore)
 */
export function getAdminUserData() {
  return {
    name: 'Admin User',
    email: 'admin@example.com',
    mobile: '',
    department: '',
    role: 'admin' as const
  };
}
