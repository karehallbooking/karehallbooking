import { db, collections } from '../config/firebase';
import { User } from '../types';

export class UserService {
  /**
   * Create a new user in Firestore
   */
  static async createUser(userData: Omit<User, 'createdAt' | 'lastLogin'>): Promise<User> {
    try {
      const userRef = db.collection(collections.users).doc(userData.uid);
      
      const newUser: User = {
        ...userData,
        createdAt: new Date() as any,
        lastLogin: new Date() as any,
      };

      await userRef.set(newUser);
      
      return newUser;
    } catch (error) {
      console.error('Error creating user:', error);
      throw new Error('Failed to create user');
    }
  }

  /**
   * Get user by UID
   */
  static async getUserById(uid: string): Promise<User | null> {
    try {
      const userDoc = await db.collection(collections.users).doc(uid).get();
      
      if (!userDoc.exists) {
        return null;
      }

      return { uid, ...userDoc.data() } as User;
    } catch (error) {
      console.error('Error fetching user:', error);
      throw new Error('Failed to fetch user');
    }
  }

  /**
   * Update user profile
   */
  static async updateUser(uid: string, updates: Partial<User>): Promise<User> {
    try {
      const userRef = db.collection(collections.users).doc(uid);
      
      // Remove fields that shouldn't be updated
      const { uid: _, createdAt, ...allowedUpdates } = updates;
      
      await userRef.update({
        ...allowedUpdates,
        updatedAt: new Date(),
      });

      const updatedDoc = await userRef.get();
      return { uid, ...updatedDoc.data() } as User;
    } catch (error) {
      console.error('Error updating user:', error);
      throw new Error('Failed to update user');
    }
  }

  /**
   * Get all users (admin only)
   */
  static async getAllUsers(limit: number = 50, offset: number = 0): Promise<User[]> {
    try {
      const snapshot = await db
        .collection(collections.users)
        .orderBy('createdAt', 'desc')
        .limit(limit)
        .offset(offset)
        .get();

      return snapshot.docs.map(doc => ({
        uid: doc.id,
        ...doc.data()
      })) as User[];
    } catch (error) {
      console.error('Error fetching users:', error);
      throw new Error('Failed to fetch users');
    }
  }

  /**
   * Search users by email or name
   */
  static async searchUsers(query: string): Promise<User[]> {
    try {
      const snapshot = await db
        .collection(collections.users)
        .where('email', '>=', query.toLowerCase())
        .where('email', '<=', query.toLowerCase() + '\uf8ff')
        .limit(20)
        .get();

      return snapshot.docs.map(doc => ({
        uid: doc.id,
        ...doc.data()
      })) as User[];
    } catch (error) {
      console.error('Error searching users:', error);
      throw new Error('Failed to search users');
    }
  }

  /**
   * Delete user (admin only)
   */
  static async deleteUser(uid: string): Promise<void> {
    try {
      await db.collection(collections.users).doc(uid).delete();
    } catch (error) {
      console.error('Error deleting user:', error);
      throw new Error('Failed to delete user');
    }
  }

  /**
   * Update user role (admin only)
   */
  static async updateUserRole(uid: string, role: 'user' | 'admin'): Promise<User> {
    try {
      const userRef = db.collection(collections.users).doc(uid);
      
      await userRef.update({
        role,
        updatedAt: new Date(),
      });

      const updatedDoc = await userRef.get();
      return { uid, ...updatedDoc.data() } as User;
    } catch (error) {
      console.error('Error updating user role:', error);
      throw new Error('Failed to update user role');
    }
  }
}