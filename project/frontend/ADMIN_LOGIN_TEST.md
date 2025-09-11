# Admin Login Test Guide

## Current Issues:
1. Admin email not redirecting to admin dashboard
2. Duplicate sidebar display
3. Need to verify admin credentials

## Test Steps:

### 1. Check Current Admin User in Firestore:
1. Go to Firebase Console → Firestore Database
2. Look for user with email `karehallbooking@gmail.com`
3. Check if `role` field is set to `"admin"`

### 2. Test Admin Login:
1. Open browser console (F12)
2. Try logging in with `karehallbooking@gmail.com`
3. Check console logs for:
   - `🔍 User data from Firestore:` - shows user data
   - `🔍 AppContent - user role:` - shows role detection
   - `🔍 AppContent - current path:` - shows current route

### 3. Manual Admin Creation (if needed):
If admin user doesn't exist or has wrong role:

```javascript
// In browser console after login
const userId = firebase.auth().currentUser.uid;
firebase.firestore().collection('users').doc(userId).set({
  uid: userId,
  name: 'KARE Hall Admin',
  email: 'karehallbooking@gmail.com',
  mobile: '',
  department: 'Administration',
  role: 'admin',
  createdAt: new Date(),
  lastLogin: new Date()
}).then(() => {
  console.log('✅ Admin user created/updated!');
  window.location.reload();
});
```

### 4. Check for Duplicate Sidebars:
Look for:
- Two sidebars visible at once
- Sidebar content appearing twice
- Layout issues in mobile view

## Expected Behavior:
- Admin login → Redirect to `/admin/dashboard`
- User login → Redirect to `/dashboard`
- Single sidebar display
- Clean layout

## Debug Information:
The console will show:
- User data from Firestore
- Role detection
- Current path
- Any errors

This will help identify where the issue is occurring.
