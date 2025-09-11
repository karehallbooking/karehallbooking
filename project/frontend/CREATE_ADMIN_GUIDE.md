# Create Admin User Guide

## Issue Fixed ✅

The hardcoded admin details (`9876543210`, `KARE Hall Admin`, `Administration`) have been removed from the codebase. Now all users are created as regular users by default, and admin promotion is handled through the UI.

## How to Create Admin User

### Option 1: Through the UI (Recommended)
1. **Register a new user** with email `karehallbooking@gmail.com`
2. **Login as any existing admin**
3. **Go to "Current Users" page**
4. **Find the user** and click **"Make Admin"** button
5. **Confirm the promotion**

### Option 2: Manual Firestore Update
1. **Register normally** with email `karehallbooking@gmail.com`
2. **Go to Firebase Console** → Firestore Database
3. **Find the user document** in `users` collection
4. **Update the `role` field** from `"user"` to `"admin"`
5. **Save the changes**

### Option 3: Using Browser Console
1. **Login to the app** with `karehallbooking@gmail.com`
2. **Open browser console** (F12)
3. **Run this code:**
```javascript
// Get current user ID
const userId = firebase.auth().currentUser.uid;

// Update user role to admin
firebase.firestore().collection('users').doc(userId).update({
  role: 'admin'
}).then(() => {
  console.log('✅ User promoted to admin successfully!');
  // Refresh the page
  window.location.reload();
});
```

## What Was Changed

### ❌ Before (Hardcoded):
- AuthContext automatically created admin users with hardcoded details
- Mobile: `9876543210`
- Name: `KARE Hall Admin`
- Department: `Administration`

### ✅ After (Dynamic):
- All users created as regular users by default
- Admin promotion handled through UI
- No hardcoded admin details
- Users keep their original information when promoted

## Benefits

1. **No More Duplicate Admins**: Each admin user has their own unique details
2. **Flexible Admin Management**: Admins can be created from any user
3. **Clean Data**: No hardcoded values cluttering the database
4. **Better Security**: Admin creation is controlled and intentional

## Next Steps

1. **Delete the old admin user** from Firebase Console if it exists
2. **Register a new user** with `karehallbooking@gmail.com`
3. **Promote them to admin** using the UI
4. **Test the admin functionality**

The app will now work correctly without creating duplicate admin users with hardcoded details!
