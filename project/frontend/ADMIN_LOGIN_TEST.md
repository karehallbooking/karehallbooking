# Admin Login Test Guide

## Fixed Issues:
✅ Admin email/password login now works properly
✅ Admin role assignment works for both login methods
✅ Consistent admin user creation
✅ **NEW:** Account linking between email/password and Google login methods
✅ **NEW:** Seamless switching between login methods for same email

## Test Steps:

### 1. Test Email/Password Login:
1. Go to the login page
2. Enter credentials:
   - **Email:** `karehallbooking@gmail.com`
   - **Password:** `Karehallbooking@198`
3. Click "Sign In"
4. **Expected:** Redirect to `/admin/dashboard`
5. Check console logs for:
   - `🔍 Found existing user by email:` (if switching from Google)
   - `🔧 Forcing admin role for admin email in login function`

### 2. Test Google Login:
1. Go to the login page
2. Click "Continue with Google"
3. Select the Google account `karehallbooking@gmail.com`
4. **Expected:** Redirect to `/admin/dashboard`
5. Check console logs for:
   - `🔍 Found existing user by email for Google login:` (if switching from email/password)
   - `🔧 Forcing admin role for admin email in Google login function`

### 3. Test Switching Between Login Methods:
1. **First:** Login with email/password → Should work and redirect to admin dashboard
2. **Logout** and login with Google → Should work and redirect to admin dashboard
3. **Logout** and login with email/password again → Should work and redirect to admin dashboard
4. **Expected:** Both methods work consistently for the same email

### 4. Verify Admin User in Firestore:
1. Go to Firebase Console → Firestore Database
2. Look for user with email `karehallbooking@gmail.com`
3. Verify the document has:
   - `role: "admin"`
   - `name: "KARE Hall Admin"`
   - `department: "Administration"`
4. **Note:** There should only be ONE document for this email (not separate ones for each auth method)

### 5. Test Regular User Login:
1. Login with any other email (not admin)
2. **Expected:** Redirect to `/dashboard` (not admin dashboard)

### 6. Debug Console Commands:
If you need to debug, run these in browser console:

```javascript
// Check current user role
const currentUser = firebase.auth().currentUser;
if (currentUser) {
  firebase.firestore().collection('users').doc(currentUser.uid).get()
    .then((doc) => {
      if (doc.exists()) {
        const userData = doc.data();
        console.log('🔍 User data:', userData);
        console.log('🔍 Is admin?', userData.role === 'admin');
        console.log('🔍 Current path:', window.location.pathname);
      }
    });
}
```

## Expected Behavior:
- ✅ Admin email/password login → Redirect to `/admin/dashboard`
- ✅ Admin Google login → Redirect to `/admin/dashboard`
- ✅ **NEW:** Switching between login methods works seamlessly
- ✅ **NEW:** Single Firestore document shared between both auth methods
- ✅ Regular user login → Redirect to `/dashboard`
- ✅ Admin role properly assigned in Firestore
- ✅ Consistent admin user data

## How the Fix Works:
1. **Email-based User Lookup:** Both login methods now search for existing users by email address
2. **Account Merging:** When switching between auth methods, the system updates the existing Firestore document with the new UID
3. **Consistent Admin Role:** Admin role is applied regardless of which login method is used
4. **Single Source of Truth:** One Firestore document per email address, regardless of authentication method

## Troubleshooting:
If admin login still doesn't work:

1. **Clear browser cache and cookies**
2. **Check Firebase Console → Authentication** to ensure email/password is enabled
3. **Verify the admin user exists in Firestore** with correct role
4. **Check console for any error messages**
5. **Look for UID mismatch messages** in console logs

## Admin Credentials:
- **Email:** `karehallbooking@gmail.com`
- **Password:** `Karehallbooking@198`
- **Role:** `admin`
- **Name:** `KARE Hall Admin`
- **Department:** `Administration`
