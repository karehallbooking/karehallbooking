import { FirestoreService } from '../services/firestoreService';

/**
 * Utility function to clean up duplicate accounts
 * This should be run by an admin to fix existing duplicate accounts
 */
export async function cleanupDuplicateAccounts(): Promise<void> {
  try {
    console.log('🔍 Starting duplicate account cleanup...');
    
    // Get all users
    const allUsers = await FirestoreService.getAllUsers();
    
    // Group users by email
    const emailGroups: { [email: string]: any[] } = {};
    
    allUsers.forEach(user => {
      if (user.email) {
        if (!emailGroups[user.email]) {
          emailGroups[user.email] = [];
        }
        emailGroups[user.email].push(user);
      }
    });
    
    // Find emails with duplicates
    const duplicateEmails = Object.keys(emailGroups).filter(email => emailGroups[email].length > 1);
    
    console.log(`Found ${duplicateEmails.length} emails with duplicate accounts:`, duplicateEmails);
    
    // Clean up each duplicate email
    for (const email of duplicateEmails) {
      console.log(`\n🧹 Cleaning up duplicates for email: ${email}`);
      await FirestoreService.mergeDuplicateAccounts(email);
    }
    
    console.log('\n✅ Duplicate account cleanup completed!');
    
  } catch (error) {
    console.error('❌ Error during duplicate account cleanup:', error);
    throw error;
  }
}

/**
 * Check for duplicate accounts without cleaning them up
 */
export async function checkForDuplicateAccounts(): Promise<{ [email: string]: any[] }> {
  try {
    console.log('🔍 Checking for duplicate accounts...');
    
    // Get all users
    const allUsers = await FirestoreService.getAllUsers();
    
    // Group users by email
    const emailGroups: { [email: string]: any[] } = {};
    
    allUsers.forEach(user => {
      if (user.email) {
        if (!emailGroups[user.email]) {
          emailGroups[user.email] = [];
        }
        emailGroups[user.email].push(user);
      }
    });
    
    // Find emails with duplicates
    const duplicateEmails = Object.keys(emailGroups).filter(email => emailGroups[email].length > 1);
    
    console.log(`Found ${duplicateEmails.length} emails with duplicate accounts:`, duplicateEmails);
    
    // Return only duplicate groups
    const duplicateGroups: { [email: string]: any[] } = {};
    duplicateEmails.forEach(email => {
      duplicateGroups[email] = emailGroups[email];
    });
    
    return duplicateGroups;
    
  } catch (error) {
    console.error('❌ Error checking for duplicate accounts:', error);
    throw error;
  }
}

