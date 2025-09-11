// Admin Setup Script
// Run this in browser console to create/update admin user

import { doc, setDoc, getDoc } from 'firebase/firestore';
import { db, collections } from '../config/firebase';

export async function setupAdminUser() {
  const adminEmail = 'karehallbooking@gmail.com';
  
  try {
    // Get current user
    const currentUser = firebase.auth().currentUser;
    
    if (!currentUser) {
      console.error('❌ No user logged in');
      return;
    }
    
    if (currentUser.email !== adminEmail) {
      console.error('❌ Current user is not the admin email');
      return;
    }
    
    // Create/update admin user document
    const adminUser = {
      uid: currentUser.uid,
      name: 'KARE Hall Admin',
      email: adminEmail,
      mobile: '',
      department: 'Administration',
      role: 'admin',
      createdAt: new Date(),
      lastLogin: new Date()
    };
    
    await setDoc(doc(db, collections.users, currentUser.uid), adminUser);
    
    console.log('✅ Admin user setup complete!');
    console.log('User data:', adminUser);
    
    // Reload page to apply changes
    window.location.reload();
    
  } catch (error) {
    console.error('❌ Error setting up admin user:', error);
  }
}

// Make it available globally
window.setupAdminUser = setupAdminUser;
