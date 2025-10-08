import React, { createContext, useContext, useEffect, useState } from 'react';
import { 
  signInWithEmailAndPassword, 
  createUserWithEmailAndPassword, 
  signOut, 
  onAuthStateChanged,
  User as FirebaseUser,
  signInWithPopup,
  GoogleAuthProvider,
  linkWithPopup,
  linkWithCredential,
  EmailAuthProvider,
  fetchSignInMethodsForEmail
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
  linkGoogleAccount: () => Promise<void>;
  linkEmailPasswordAccount: (email: string, password: string) => Promise<void>;
  linkGoogleToExistingAccount: (email: string, password: string) => Promise<User>;
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

  // Helper function to check if email has multiple sign-in methods
  const checkSignInMethods = async (email: string): Promise<string[]> => {
    try {
      const methods = await fetchSignInMethodsForEmail(auth, email);
      return methods;
    } catch (error) {
      console.log('No existing account found for email:', email);
      return [];
    }
  };

  // Link Google account to existing email/password account
  const linkGoogleAccount = async (): Promise<void> => {
    if (!auth.currentUser) {
      throw new Error('No user is currently signed in');
    }

    try {
      console.log('🔗 Starting Google account linking process...');
      console.log('🔍 Current user:', auth.currentUser.email);
      
      // Check if Google is already linked
      const providerData = auth.currentUser.providerData;
      const hasGoogleProvider = providerData.some(provider => provider.providerId === 'google.com');
      
      if (hasGoogleProvider) {
        throw new Error('Google account is already linked to this account');
      }
      
      const provider = new GoogleAuthProvider();
      const result = await linkWithPopup(auth.currentUser, provider);
      console.log('✅ Google account linked successfully');
      console.log('🔍 Linked user:', result.user.email);
      
      // Update user data in Firestore
      const userDoc = await getDoc(doc(db, collections.users, auth.currentUser.uid));
      if (userDoc.exists()) {
        await updateDoc(doc(db, collections.users, auth.currentUser.uid), {
          lastLogin: new Date(),
          linkedProviders: ['email', 'google'] // Track linked providers
        });
      }
      
      // Refresh the current user data
      const updatedUserDoc = await getDoc(doc(db, collections.users, auth.currentUser.uid));
      if (updatedUserDoc.exists()) {
        const userData = updatedUserDoc.data() as User;
        const user: User = {
          ...userData,
          uid: auth.currentUser.uid
        };
        setCurrentUser(user);
      }
      
    } catch (error: any) {
      console.error('Error linking Google account:', error);
      if (error.code === 'auth/provider-already-linked') {
        throw new Error('Google account is already linked to this account');
      } else if (error.code === 'auth/credential-already-in-use') {
        throw new Error('This Google account is already linked to another account');
      } else {
        throw new Error(error.message || 'Failed to link Google account');
      }
    }
  };

  // Link email/password account to existing Google account
  const linkEmailPasswordAccount = async (email: string, password: string): Promise<void> => {
    if (!auth.currentUser) {
      throw new Error('No user is currently signed in');
    }

    try {
      console.log('🔗 Starting email/password account linking process...');
      console.log('🔍 Current user:', auth.currentUser.email);
      console.log('🔍 Linking email:', email);
      
      // Check if email/password is already linked
      const providerData = auth.currentUser.providerData;
      const hasEmailProvider = providerData.some(provider => provider.providerId === 'password');
      
      if (hasEmailProvider) {
        throw new Error('Email/password account is already linked to this account');
      }
      
      const credential = EmailAuthProvider.credential(email, password);
      const result = await linkWithCredential(auth.currentUser, credential);
      console.log('✅ Email/password account linked successfully');
      console.log('🔍 Linked user:', result.user.email);
      
      // Update user data in Firestore
      const userDoc = await getDoc(doc(db, collections.users, auth.currentUser.uid));
      if (userDoc.exists()) {
        await updateDoc(doc(db, collections.users, auth.currentUser.uid), {
          lastLogin: new Date(),
          linkedProviders: ['google', 'email'] // Track linked providers
        });
      }
      
      // Refresh the current user data
      const updatedUserDoc = await getDoc(doc(db, collections.users, auth.currentUser.uid));
      if (updatedUserDoc.exists()) {
        const userData = updatedUserDoc.data() as User;
        const user: User = {
          ...userData,
          uid: auth.currentUser.uid
        };
        setCurrentUser(user);
      }
      
    } catch (error: any) {
      console.error('Error linking email/password account:', error);
      if (error.code === 'auth/provider-already-linked') {
        throw new Error('Email/password account is already linked to this account');
      } else if (error.code === 'auth/credential-already-in-use') {
        throw new Error('This email/password account is already linked to another account');
      } else if (error.code === 'auth/invalid-credential') {
        throw new Error('Invalid email or password');
      } else {
        throw new Error(error.message || 'Failed to link email/password account');
      }
    }
  };

  // Link Google account to existing email/password account (special function for account merging)
  const linkGoogleToExistingAccount = async (email: string, password: string): Promise<User> => {
    try {
      console.log('🔗 Starting Google linking to existing email/password account...');
      console.log('🔍 Email:', email);
      
      // First, sign in with email/password to get the existing account
      console.log('🔑 Signing in with existing email/password account...');
      const emailResult = await signInWithEmailAndPassword(auth, email, password);
      const existingUser = emailResult.user;
      
      console.log('✅ Signed in with email/password, UID:', existingUser.uid);
      
      // Now link the Google provider to this account
      console.log('🔗 Linking Google provider to existing account...');
      const provider = new GoogleAuthProvider();
      const linkResult = await linkWithPopup(existingUser, provider);
      
      console.log('✅ Google provider linked successfully!');
      console.log('🔍 Final user providers:', linkResult.user.providerData.map(p => p.providerId));
      
      // Update Firestore with linked providers info
      const userDoc = await getDoc(doc(db, collections.users, existingUser.uid));
      if (userDoc.exists()) {
        await updateDoc(doc(db, collections.users, existingUser.uid), {
          lastLogin: new Date(),
          linkedProviders: ['email', 'google']
        });
      }
      
      // Get updated user data
      const updatedUserDoc = await getDoc(doc(db, collections.users, existingUser.uid));
      if (updatedUserDoc.exists()) {
        const userData = updatedUserDoc.data() as User;
        const user: User = {
          ...userData,
          uid: existingUser.uid
        };
        
        // Force admin role for admin email
        if (email === 'karehallbooking@gmail.com' && user.role !== 'admin') {
          console.log('🔧 Forcing admin role for admin email in linking function');
          await updateDoc(doc(db, collections.users, existingUser.uid), {
            role: 'admin',
            name: 'KARE Hall Admin',
            department: 'Administration'
          });
          user.role = 'admin';
          user.name = 'KARE Hall Admin';
          user.department = 'Administration';
        }
        
        setCurrentUser(user);
        return user;
      } else {
        throw new Error('User document not found after linking');
      }
      
    } catch (error: any) {
      console.error('Error linking Google to existing account:', error);
      if (error.code === 'auth/provider-already-linked') {
        throw new Error('Google account is already linked to this account');
      } else if (error.code === 'auth/credential-already-in-use') {
        throw new Error('This Google account is already linked to another account');
      } else if (error.code === 'auth/invalid-credential') {
        throw new Error('Invalid email or password');
      } else {
        throw new Error(error.message || 'Failed to link Google account');
      }
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
      
      // First, try to get the user document by UID (this should work after authentication)
      let existingUser: User | null = null;
      try {
        const userDoc = await getDoc(doc(db, collections.users, firebaseUser.uid));
        if (userDoc.exists()) {
          existingUser = { ...userDoc.data(), uid: firebaseUser.uid } as User;
          console.log('🔍 Found existing user by UID:', existingUser);
        }
      } catch (error) {
        console.log('🔍 No user document found by UID, will create new one');
      }
      
      if (existingUser) {
        // Update last login
        await updateDoc(doc(db, collections.users, firebaseUser.uid), {
          lastLogin: new Date()
        });
        
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
        
        // Set the current user immediately
        setCurrentUser(existingUser);
        return existingUser;
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
        // Set the current user immediately
        setCurrentUser(newUser);
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
      
      console.log('🔍 Google login successful for:', firebaseUser.email);
      console.log('🔍 Firebase UID:', firebaseUser.uid);
      
      // Check if email already exists with email/password method in Firestore
      const existingUserByEmail = await findUserByEmail(firebaseUser.email || '');
      if (existingUserByEmail && existingUserByEmail.uid !== firebaseUser.uid) {
        console.log('🔗 Found existing email/password account in Firestore, attempting to link...');
        
        // Check Firebase sign-in methods for this email
        const signInMethods = await fetchSignInMethodsForEmail(auth, firebaseUser.email || '');
        console.log('🔍 Available Firebase sign-in methods:', signInMethods);
        
        if (signInMethods.includes('password')) {
          console.log('🔗 Email/password method exists in Firebase, need to link accounts');
          
          // Sign out current Google user
          await signOut(auth);
          
          // This is a complex linking process that requires user to login with email/password first
          // For now, we'll provide a clear message to the user
          throw new Error(`Account linking required! An account with email ${firebaseUser.email} already exists with email/password login. Please login with your email and password first, then you can link your Google account from your profile page.`);
        } else {
          console.log('🔗 No email/password method in Firebase, but exists in Firestore - merging accounts');
          
          // Merge the accounts by updating the existing Firestore document with Google UID
          console.log('🔄 Merging accounts - updating existing Firestore document...');
          
          // Delete the old Google account document and update the existing one
          await deleteDoc(doc(db, collections.users, firebaseUser.uid));
          
          // Update the existing email/password account with Google UID
          const updatedUser = {
            ...existingUserByEmail,
            uid: firebaseUser.uid, // Use the Google UID
            lastLogin: new Date(),
            linkedProviders: ['email', 'google']
          };
          
          await setDoc(doc(db, collections.users, firebaseUser.uid), updatedUser);
          console.log('✅ Accounts merged successfully');
          
          // Set the current user immediately
          setCurrentUser(updatedUser);
          return updatedUser;
        }
      }
      
      // First, try to get the user document by UID (this should work after authentication)
      let existingUser: User | null = null;
      try {
        const userDoc = await getDoc(doc(db, collections.users, firebaseUser.uid));
        if (userDoc.exists()) {
          existingUser = { ...userDoc.data(), uid: firebaseUser.uid } as User;
          console.log('🔍 Found existing user by UID for Google login:', existingUser);
        }
      } catch (error) {
        console.log('🔍 No user document found by UID for Google login, will create new one');
      }
      
      if (existingUser) {
        // Update last login
        await updateDoc(doc(db, collections.users, firebaseUser.uid), {
          lastLogin: new Date()
        });
        
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
        
        // Set the current user immediately
        setCurrentUser(existingUser);
        return existingUser;
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
        
        // Set the current user immediately
        setCurrentUser(newUser);
        return newUser;
      }
    } catch (error: any) {
      // Handle account exists with different credential error
      if (error.code === 'auth/account-exists-with-different-credential') {
        console.log('🔄 Account exists with different credential, checking sign-in methods...');
        
        // Get the email from the error
        const email = error.customData?.email;
        if (!email) {
          throw new Error('Unable to determine email for account linking');
        }
        
        // Check what sign-in methods are available
        const signInMethods = await checkSignInMethods(email);
        console.log('🔍 Available sign-in methods:', signInMethods);
        
        if (signInMethods.includes('password')) {
          throw new Error('ACCOUNT_EXISTS_WITH_PASSWORD');
        } else {
          throw new Error('ACCOUNT_EXISTS_WITH_GOOGLE');
        }
      }
      
      console.error('Google login error:', error);
      throw new Error(error.message || 'Google login failed');
    }
  };

  const register = async (userData: Omit<User, 'uid' | 'role'> & { password: string }): Promise<User> => {
    try {
      // Check if email already exists in Firestore
      const existingUser = await findUserByEmail(userData.email);
      if (existingUser) {
        throw new Error('An account with this email already exists. Please use a different email or try logging in.');
      }

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
    loading,
    linkGoogleAccount,
    linkEmailPasswordAccount,
    linkGoogleToExistingAccount
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}