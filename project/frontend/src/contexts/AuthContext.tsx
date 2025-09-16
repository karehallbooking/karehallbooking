import React, { createContext, useContext, useEffect, useState } from 'react';
import { 
  signInWithEmailAndPassword, 
  createUserWithEmailAndPassword, 
  signOut, 
  onAuthStateChanged,
  User as FirebaseUser,
  signInWithPopup,
  GoogleAuthProvider
} from 'firebase/auth';
import { doc, getDoc, setDoc, updateDoc, deleteDoc, collection, query, where, getDocs } from 'firebase/firestore';
import { auth, db, collections } from '../config/firebase';
import { User } from '../types';

interface AuthContextType {
  currentUser: User | null;
  firebaseToken: string | null;
  login: (email: string, password: string) => Promise<User>;
  loginWithGoogle: () => Promise<User>;
  register: (userData: Omit<User, 'uid' | 'role'> & { password: string }) => Promise<User>;
  logout: () => void;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [firebaseToken, setFirebaseToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  // Helper function to find existing user document by email
  const findUserByEmail = async (email: string): Promise<User | null> => {
    try {
      // Query Firestore for user with this email
      const usersRef = collection(db, collections.users);
      const q = query(usersRef, where('email', '==', email));
      const querySnapshot = await getDocs(q);
      
      if (!querySnapshot.empty) {
        const userDoc = querySnapshot.docs[0];
        return { ...userDoc.data(), uid: userDoc.id } as User;
      }
      return null;
    } catch (error) {
      console.error('Error finding user by email:', error);
      return null;
    }
  };

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser: FirebaseUser | null) => {
      if (firebaseUser) {
        // Get Firebase token
        try {
          const token = await firebaseUser.getIdToken();
          setFirebaseToken(token);
          localStorage.setItem('firebaseToken', token);
        } catch (error) {
          console.error('Error getting Firebase token:', error);
        }

        // Get user data from Firestore
        try {
          const userDoc = await getDoc(doc(db, collections.users, firebaseUser.uid));
          if (userDoc.exists()) {
            const userData = userDoc.data() as User;
            const user: User = {
              ...userData,
              uid: firebaseUser.uid
            };
            console.log('🔍 User data from Firestore:', user);
            console.log('🔍 User role:', user.role);
            console.log('🔍 User email:', user.email);
            
            // Force admin role for admin email
            if (firebaseUser.email === 'karehallbooking@gmail.com' && user.role !== 'admin') {
              console.log('🔧 Forcing admin role for admin email');
              await updateDoc(doc(db, collections.users, firebaseUser.uid), {
                role: 'admin',
                name: 'KARE Hall Admin',
                department: 'Administration'
              });
              user.role = 'admin';
              user.name = 'KARE Hall Admin';
              user.department = 'Administration';
              console.log('🔧 Admin role updated, user object:', user);
            }
            
            setCurrentUser(user);
            
          } else {
            // User document doesn't exist, create it
            const isAdmin = firebaseUser.email === 'karehallbooking@gmail.com';
            const newUser: User = {
              uid: firebaseUser.uid,
              name: isAdmin ? 'KARE Hall Admin' : (firebaseUser.displayName || 'User'),
              email: firebaseUser.email || '',
              mobile: '',
              department: isAdmin ? 'Administration' : '',
              role: isAdmin ? 'admin' : 'user'
            };
            console.log('🔍 Creating new user:', newUser);
            console.log('🔍 Is admin email?', isAdmin);
            await setDoc(doc(db, collections.users, firebaseUser.uid), {
              ...newUser,
              createdAt: new Date(),
              lastLogin: new Date()
            });
            setCurrentUser(newUser);
            
          }
        } catch (error) {
          console.error('Error fetching user data:', error);
          setCurrentUser(null);
        }
      } else {
        setCurrentUser(null);
        setFirebaseToken(null);
        localStorage.removeItem('firebaseToken');
      }
      setLoading(false);
    });

    return unsubscribe;
  }, []);

  const login = async (email: string, password: string): Promise<User> => {
    try {
      const userCredential = await signInWithEmailAndPassword(auth, email, password);
      const firebaseUser = userCredential.user;
      
      // Check if there's an existing user document with this email (from any auth method)
      const existingUser = await findUserByEmail(email);
      
      if (existingUser) {
        console.log('🔍 Found existing user by email:', existingUser);
        
        // Update the existing user document with the new UID if different
        if (existingUser.uid !== firebaseUser.uid) {
          console.log('🔄 UID mismatch, updating existing user document with new UID');
          await updateDoc(doc(db, collections.users, existingUser.uid), {
            uid: firebaseUser.uid,
            lastLogin: new Date()
          });
          // Delete the old document
          await deleteDoc(doc(db, collections.users, existingUser.uid));
        } else {
          // Just update last login
          await updateDoc(doc(db, collections.users, firebaseUser.uid), {
            lastLogin: new Date()
          });
        }
        
        // Force admin role for admin email
        if (firebaseUser.email === 'karehallbooking@gmail.com' && existingUser.role !== 'admin') {
          console.log('🔧 Forcing admin role for admin email in login function');
          await updateDoc(doc(db, collections.users, firebaseUser.uid), {
            role: 'admin',
            name: 'KARE Hall Admin',
            department: 'Administration'
          });
          existingUser.role = 'admin';
          existingUser.name = 'KARE Hall Admin';
          existingUser.department = 'Administration';
          console.log('🔧 Admin role updated in login, user object:', existingUser);
        }
        
        return { ...existingUser, uid: firebaseUser.uid };
      } else {
        // No existing user found, create new one
        const isAdmin = firebaseUser.email === 'karehallbooking@gmail.com';
        const newUser: User = {
          uid: firebaseUser.uid,
          name: isAdmin ? 'KARE Hall Admin' : (firebaseUser.displayName || 'User'),
          email: firebaseUser.email || '',
          mobile: '',
          department: isAdmin ? 'Administration' : '',
          role: isAdmin ? 'admin' : 'user'
        };
        console.log('🔍 Creating new user in login function:', newUser);
        console.log('🔍 Is admin email?', isAdmin);
        await setDoc(doc(db, collections.users, firebaseUser.uid), {
          ...newUser,
          createdAt: new Date(),
          lastLogin: new Date()
        });
        return newUser;
      }
    } catch (error: any) {
      console.error('Login error:', error);
      throw new Error(error.message || 'Login failed');
    }
  };

  const loginWithGoogle = async (): Promise<User> => {
    try {
      const provider = new GoogleAuthProvider();
      const result = await signInWithPopup(auth, provider);
      const firebaseUser = result.user;
      
      // Check if there's an existing user document with this email (from any auth method)
      const existingUser = await findUserByEmail(firebaseUser.email || '');
      
      if (existingUser) {
        console.log('🔍 Found existing user by email for Google login:', existingUser);
        
        // Update the existing user document with the new UID if different
        if (existingUser.uid !== firebaseUser.uid) {
          console.log('🔄 UID mismatch, updating existing user document with new UID for Google login');
          await updateDoc(doc(db, collections.users, existingUser.uid), {
            uid: firebaseUser.uid,
            lastLogin: new Date()
          });
          // Delete the old document
          await deleteDoc(doc(db, collections.users, existingUser.uid));
        } else {
          // Just update last login
          await updateDoc(doc(db, collections.users, firebaseUser.uid), {
            lastLogin: new Date()
          });
        }
        
        // Force admin role for admin email
        if (firebaseUser.email === 'karehallbooking@gmail.com' && existingUser.role !== 'admin') {
          console.log('🔧 Forcing admin role for admin email in Google login function');
          await updateDoc(doc(db, collections.users, firebaseUser.uid), {
            role: 'admin',
            name: 'KARE Hall Admin',
            department: 'Administration'
          });
          existingUser.role = 'admin';
          existingUser.name = 'KARE Hall Admin';
          existingUser.department = 'Administration';
          console.log('🔧 Admin role updated in Google login, user object:', existingUser);
        }
        
        return { ...existingUser, uid: firebaseUser.uid };
      } else {
        // No existing user found, create new one
        const isAdmin = firebaseUser.email === 'karehallbooking@gmail.com';
        const newUser: User = {
          uid: firebaseUser.uid,
          name: isAdmin ? 'KARE Hall Admin' : (firebaseUser.displayName || 'User'),
          email: firebaseUser.email || '',
          mobile: '',
          designation: 'Faculty',
          department: isAdmin ? 'Administration' : '',
          role: isAdmin ? 'admin' : 'user'
        };
        
        await setDoc(doc(db, collections.users, firebaseUser.uid), {
          ...newUser,
          createdAt: new Date(),
          lastLogin: new Date()
        });

        // Call backend API to trigger welcome email for new Google users
        try {
          const token = await firebaseUser.getIdToken();
          console.log('🔄 Calling backend API for Google user welcome email...');
          const response = await fetch('https://karehallbooking-g695.onrender.com/api/auth/register', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
              uid: firebaseUser.uid,
              name: firebaseUser.displayName || 'User',
              mobile: '',
              email: firebaseUser.email || '',
              designation: 'Faculty',
              department: ''
            })
          });

          console.log('📡 Backend response status for Google user:', response.status);
          if (!response.ok) {
            const errorText = await response.text();
            console.warn('Backend registration failed for Google user, but Firebase registration succeeded:', response.status, errorText);
          } else {
            console.log('✅ Backend registration successful for Google user, welcome email sent');
          }
        } catch (backendError) {
          console.warn('Backend registration failed for Google user, but Firebase registration succeeded:', backendError);
          // Don't fail the registration if backend call fails
        }
        
        return newUser;
      }
    } catch (error: any) {
      console.error('Google login error:', error);
      throw new Error(error.message || 'Google login failed');
    }
  };

  const register = async (userData: Omit<User, 'uid' | 'role'> & { password: string }): Promise<User> => {
    try {
      const { password, ...userInfo } = userData;
      const userCredential = await createUserWithEmailAndPassword(auth, userData.email, password);
      const firebaseUser = userCredential.user;
      
      // Create user as regular user (admin promotion handled separately)
      const newUser: User = {
        ...userInfo,
        uid: firebaseUser.uid,
        role: 'user'
      };
      
      // Save user data to Firestore
      await setDoc(doc(db, collections.users, firebaseUser.uid), {
        ...newUser,
        createdAt: new Date(),
        lastLogin: new Date()
      });

      // Call backend API to trigger welcome email
      try {
        const token = await firebaseUser.getIdToken();
        console.log('🔄 Calling backend API for welcome email...');
        const response = await fetch('https://karehallbooking-g695.onrender.com/api/auth/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({
            uid: firebaseUser.uid,
            name: userData.name,
            mobile: userData.mobile,
            email: userData.email,
            designation: 'Faculty', // Default designation
            department: userData.department
          })
        });

        console.log('📡 Backend response status:', response.status);
        if (!response.ok) {
          const errorText = await response.text();
          console.warn('Backend registration failed, but Firebase registration succeeded:', response.status, errorText);
        } else {
          console.log('✅ Backend registration successful, welcome email sent');
        }
      } catch (backendError) {
        console.warn('Backend registration failed, but Firebase registration succeeded:', backendError);
        // Don't fail the registration if backend call fails
      }
      
      return newUser;
    } catch (error: any) {
      console.error('Registration error:', error);
      throw new Error(error.message || 'Registration failed');
    }
  };

  const logout = async () => {
    try {
      await signOut(auth);
      setCurrentUser(null);
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  const value = {
    currentUser,
    firebaseToken,
    login,
    loginWithGoogle,
    register,
    logout,
    loading
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}