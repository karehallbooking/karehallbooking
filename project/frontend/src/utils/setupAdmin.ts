import { createUserWithEmailAndPassword } from 'firebase/auth';
import { doc, setDoc, getDoc, updateDoc } from 'firebase/firestore';
import { auth, db, collections } from '../config/firebase';
import { User } from '../types';

/**
 * Setup admin user in Firebase
 * This function should be called once to create the initial admin user
 */
export async function setupAdminUser(): Promise<void> {
  const adminEmail = 'karehallbooking@gmail.com';
  const adminPassword = 'Karehallbooking@198';
  
  try {
    // Create Firebase Auth user
    const userCredential = await createUserWithEmailAndPassword(auth, adminEmail, adminPassword);
    const firebaseUser = userCredential.user;
    
    // Create admin user document in Firestore
    const adminUser: User = {
      uid: firebaseUser.uid,
      name: 'KARE Hall Admin',
      email: adminEmail,
      mobile: '',
      department: 'Administration',
      role: 'admin'
    };
    
    await setDoc(doc(db, collections.users, firebaseUser.uid), {
      ...adminUser,
      createdAt: new Date(),
      lastLogin: new Date()
    });
    
    console.log('✅ Admin user created successfully!');
    console.log('Email:', adminEmail);
    console.log('Password:', adminPassword);
    console.log('UID:', firebaseUser.uid);
    
  } catch (error: any) {
    if (error.code === 'auth/email-already-in-use') {
      console.log('ℹ️ Admin user already exists in Firebase Auth');
      // Check if user document exists in Firestore
      try {
        const userDoc = await getDoc(doc(db, collections.users, firebaseUser.uid));
        if (!userDoc.exists()) {
          // Create user document if it doesn't exist
          const adminUser: User = {
            uid: firebaseUser.uid,
            name: 'KARE Hall Admin',
            email: adminEmail,
            mobile: '',
            department: 'Administration',
            role: 'admin'
          };
          
          await setDoc(doc(db, collections.users, firebaseUser.uid), {
            ...adminUser,
            createdAt: new Date(),
            lastLogin: new Date()
          });
          
          console.log('✅ Admin user document created in Firestore!');
        } else {
          console.log('ℹ️ Admin user document already exists in Firestore');
        }
      } catch (docError) {
        console.error('❌ Error creating admin user document:', docError);
      }
    } else {
      console.error('❌ Error creating admin user:', error);
      throw error;
    }
  }
}

/**
 * Update existing user to admin role
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
