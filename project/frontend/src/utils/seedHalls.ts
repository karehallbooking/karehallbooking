import { collection, addDoc, getDocs, deleteDoc, doc } from 'firebase/firestore';
import { db, collections } from '../config/firebase';

// Flag to prevent multiple cleanups from running simultaneously
let isCleaningUp = false;

// Only these 7 halls should exist in the system
const sampleHalls = [
  {
    name: 'K. S. Krishnan Auditorium',
    capacity: 500,
    facilities: ['Audio System', 'Projector', 'Air Conditioning', 'Stage', 'Microphones'],
    image: 'https://kalasalingam.ac.in/wp-content/uploads/2021/05/IMG_1827-scaled.jpg',
    available: true
  },
  {
    name: 'Dr. V. Vasudevan Seminar Hall',
    capacity: 200,
    facilities: ['Audio System', 'Projector', 'Air Conditioning', 'Whiteboard'],
    image: 'https://th-i.thgim.com/public/incoming/8jwn1a/article68094565.ece/alternates/FREE_1200/23April_Campus_Kalasa.jpg',
    available: true
  },
  {
    name: 'Admin Block Seminar Hall',
    capacity: 80,
    facilities: ['Projector', 'Air Conditioning', 'Whiteboard', 'Microphones'],
    image: 'https://th-i.thgim.com/public/incoming/xgoon1/article68068023.ece/alternates/FREE_1200/16April_Campus_Kalasa.jpg',
    available: true
  },
  {
    name: 'Srinivasa Ramanujam Block Seminar Hall',
    capacity: 120,
    facilities: ['Audio System', 'Projector', 'Air Conditioning', 'Whiteboard', 'Microphones'],
    image: 'https://app.afternoonnews.in/storage/images/5/1Ff6G694j4thp0GWuXwp6i58oob3fpPIdVlJd3OF.jpg',
    available: true
  },
  {
    name: 'Dr. A. P. J. Abdul Kalam Block Seminar Hall',
    capacity: 150,
    facilities: ['Audio System', 'Projector', 'Air Conditioning', 'Stage', 'Whiteboard', 'Microphones'],
    image: 'https://kalasalingam.ac.in/wp-content/uploads/2023/08/IMG_7759-scaled.jpg',
    available: true
  },
  {
    name: 'Dr. S. Radha Krishnan Senate Hall',
    capacity: 200,
    facilities: ['Audio System', 'Projector', 'Air Conditioning', 'Stage', 'Microphones', 'Conference Table'],
    image: 'https://thenews21.com/wp-content/uploads/2023/02/WhatsApp-Image-2023-02-26-at-8.23.14-PM-1-1024x682.jpeg',
    available: true
  }
];

export async function seedHalls(): Promise<void> {
  try {
    // Check if halls already exist
    const hallsSnapshot = await getDocs(collection(db, collections.halls));
    
    if (hallsSnapshot.empty) {
      console.log('🌱 No halls found, seeding exactly 7 halls...');
      
      // Add only the 7 required halls to Firestore
      for (const hall of sampleHalls) {
        await addDoc(collection(db, collections.halls), {
          ...hall,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        });
      }
      
      console.log('✅ Exactly 7 halls seeded successfully!');
    } else {
      console.log('ℹ️ Halls already exist, skipping seed');
    }
  } catch (error) {
    console.error('❌ Error seeding halls:', error);
    throw error;
  }
}

// Function to clean up duplicate halls and ensure only 7 unique halls exist
export async function cleanupHalls(): Promise<void> {
  try {
    console.log('🧹 Checking halls collection...');
    const hallsSnapshot = await getDocs(collection(db, collections.halls));
    
    // Check if we have exactly the 7 required halls with no duplicates
    const existingHalls = hallsSnapshot.docs.map(doc => doc.data().name);
    const requiredHallNames = sampleHalls.map(hall => hall.name);
    
    // Check if we have exactly the right halls
    const hasCorrectHalls = requiredHallNames.every(name => existingHalls.includes(name)) && 
                           existingHalls.length === 7 &&
                           new Set(existingHalls).size === 7; // No duplicates
    
    if (hasCorrectHalls) {
      console.log('✅ Halls collection is already correct with exactly 7 unique halls');
      return;
    }
    
    console.log('🧹 Cleaning up duplicate halls...');
    
    // Delete all existing halls
    for (const hallDoc of hallsSnapshot.docs) {
      await deleteDoc(doc(db, collections.halls, hallDoc.id));
    }
    
    // Add only the 7 required halls back
    console.log('🌱 Adding exactly 7 unique halls...');
    for (const hall of sampleHalls) {
      await addDoc(collection(db, collections.halls), {
        ...hall,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      });
    }
    
    console.log('✅ Hall cleanup completed! Now showing exactly 7 halls.');
  } catch (error) {
    console.error('❌ Error cleaning up halls:', error);
    throw error;
  }
}

// Force cleanup function to completely reset halls collection
export async function forceCleanupHalls(): Promise<void> {
  // Prevent multiple cleanups from running simultaneously
  if (isCleaningUp) {
    console.log('⏳ Cleanup already in progress, waiting...');
    return new Promise((resolve) => {
      const checkInterval = setInterval(() => {
        if (!isCleaningUp) {
          clearInterval(checkInterval);
          resolve();
        }
      }, 100);
    });
  }

  try {
    isCleaningUp = true;
    console.log('🧹 Force cleaning all halls...');
    
    // Get all existing halls
    const hallsSnapshot = await getDocs(collection(db, collections.halls));
    console.log(`📊 Found ${hallsSnapshot.docs.length} existing halls`);
    
    // Delete all existing halls in batches to avoid timeout
    const deletePromises = hallsSnapshot.docs.map(hallDoc => 
      deleteDoc(doc(db, collections.halls, hallDoc.id))
    );
    
    console.log(`🗑️ Deleting ${hallsSnapshot.docs.length} existing halls...`);
    await Promise.all(deletePromises);
    
    // Wait a moment to ensure deletions are processed
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Add only the 7 required halls back
    console.log('🌱 Adding exactly 7 unique halls...');
    const addPromises = sampleHalls.map(hall => 
      addDoc(collection(db, collections.halls), {
        ...hall,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      })
    );
    
    await Promise.all(addPromises);
    
    console.log('✅ Force cleanup completed! Now showing exactly 7 halls.');
  } catch (error) {
    console.error('❌ Error force cleaning halls:', error);
    throw error;
  } finally {
    isCleaningUp = false;
  }
}
