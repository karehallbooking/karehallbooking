import admin from "firebase-admin";
import dotenv from "dotenv";

dotenv.config();

// Check for required env vars
if (
  !process.env.FIREBASE_PROJECT_ID ||
  !process.env.FIREBASE_CLIENT_EMAIL ||
  !process.env.FIREBASE_PRIVATE_KEY
) {
  throw new Error("❌ Missing Firebase environment variables in .env file");
}

// Initialize Firebase Admin SDK with .env values
admin.initializeApp({
  credential: admin.credential.cert({
    projectId: process.env.FIREBASE_PROJECT_ID,
    clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
    privateKey: process.env.FIREBASE_PRIVATE_KEY.replace(/\\n/g, "\n"),
  }),
});

export const db = admin.firestore();
export const auth = admin.auth();

// Firestore collections
export const collections = {
  users: 'users',
  bookings: 'bookings',
  halls: 'halls',
  notifications: 'notifications',
} as const;

export default admin;