// Test Admin Role Function
// Run this in browser console to test admin role

export function testAdminRole() {
  const currentUser = firebase.auth().currentUser;
  
  if (!currentUser) {
    console.log('❌ No user logged in');
    return;
  }
  
  console.log('🔍 Current user email:', currentUser.email);
  console.log('🔍 Current user UID:', currentUser.uid);
  
  // Check Firestore document
  firebase.firestore().collection('users').doc(currentUser.uid).get()
    .then((doc) => {
      if (doc.exists()) {
        const userData = doc.data();
        console.log('🔍 User data from Firestore:', userData);
        console.log('🔍 User role:', userData.role);
        console.log('🔍 Is admin?', userData.role === 'admin');
        console.log('🔍 Current path:', window.location.pathname);
        
        if (userData.role === 'admin') {
          console.log('✅ User is admin - should redirect to /admin/dashboard');
        } else {
          console.log('❌ User is not admin - should redirect to /dashboard');
        }
      } else {
        console.log('❌ User document does not exist in Firestore');
      }
    })
    .catch((error) => {
      console.error('❌ Error getting user data:', error);
    });
}

// Make it available globally
window.testAdminRole = testAdminRole;
