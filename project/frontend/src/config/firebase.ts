import { initializeApp } from 'firebase/app';
import { getAuth } from 'firebase/auth';
import { getFirestore } from 'firebase/firestore';

const firebaseConfig = {
  apiKey: "AIzaSyBp5JZi-4eSdRdYLHrld81ngnPd8BEhiKM",
  authDomain: "kare-hall-booking.firebaseapp.com",
  projectId: "kare-hall-booking",
  storageBucket: "kare-hall-booking.firebasestorage.app",
  messagingSenderId: "590581451510",
  appId: "1:590581451510:web:dd598be31a00dc2d2e3ad7",
  measurementId: "G-E7FXYNR13D"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Firebase Authentication and get a reference to the service
export const auth = getAuth(app);

// Initialize Cloud Firestore and get a reference to the service
export const db = getFirestore(app);

// Firestore collections
export const collections = {
  users: 'users',
  bookings: 'bookings',
  halls: 'halls',
  notifications: 'notifications',
} as const;

export default app;







