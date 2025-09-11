# Manual Admin Setup Guide

## Quick Fix for Admin Login Issue

If the admin email `karehallbooking@gmail.com` is not redirecting to the admin dashboard, follow these steps:

### Step 1: Login with Admin Email
1. Login with `karehallbooking@gmail.com`
2. Open browser console (F12)
3. Check the console logs for user data

### Step 2: Manual Admin Setup (Run in Console)
Copy and paste this code in the browser console:

```javascript
// Get current user
const currentUser = firebase.auth().currentUser;
if (!currentUser) {
  console.error('❌ No user logged in');
} else {
  console.log('🔍 Current user:', currentUser.email);
  
  // Update user document to admin
  firebase.firestore().collection('users').doc(currentUser.uid).set({
    uid: currentUser.uid,
    name: 'KARE Hall Admin',
    email: currentUser.email,
    mobile: '',
    department: 'Administration',
    role: 'admin',
    createdAt: new Date(),
    lastLogin: new Date()
  }, { merge: true }).then(() => {
    console.log('✅ Admin user updated successfully!');
    console.log('🔄 Reloading page...');
    window.location.reload();
  }).catch((error) => {
    console.error('❌ Error updating admin user:', error);
  });
}
```

### Step 3: Verify Admin Role
After running the script, check the console for:
- `✅ Admin user updated successfully!`
- The page should reload automatically
- You should be redirected to `/admin/dashboard`

### Step 4: Check Firestore (Optional)
1. Go to Firebase Console → Firestore Database
2. Find the user document with email `karehallbooking@gmail.com`
3. Verify that `role` field is set to `"admin"`

## Alternative: Direct Firestore Update

If the console method doesn't work:

1. **Go to Firebase Console**
2. **Navigate to Firestore Database**
3. **Find the users collection**
4. **Locate the document with email `karehallbooking@gmail.com`**
5. **Edit the document and set:**
   - `role`: `"admin"`
   - `name`: `"KARE Hall Admin"`
   - `department`: `"Administration"`
6. **Save the changes**
7. **Refresh the app**

## Expected Result

After completing these steps:
- ✅ Admin login should redirect to `/admin/dashboard`
- ✅ User login should redirect to `/dashboard`
- ✅ No more duplicate sidebars
- ✅ Clean admin interface

## Debug Information

The console will show:
- Current user email
- User data from Firestore
- Role detection
- Any errors

This will help identify exactly what's happening with the admin login.
