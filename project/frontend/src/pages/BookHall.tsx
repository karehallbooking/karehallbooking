import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { DashboardLayout } from '../components/DashboardLayout';
import { Building, Users, MapPin } from 'lucide-react';
import { FirestoreService } from '../services/firestoreService';
import { seedHalls, cleanupHalls, forceCleanupHalls } from '../utils/seedHalls';
import { Hall } from '../types';

export function BookHall() {
  const [halls, setHalls] = useState<Hall[]>([]);
  const [loading, setLoading] = useState(true);
  const [cleanupDone, setCleanupDone] = useState(false);

  useEffect(() => {
    loadHalls();
  }, []);

  const loadHalls = async () => {
    try {
      setLoading(true);
      
      // Check if cleanup has already been done in this session
      const sessionCleanupKey = 'halls-cleanup-done';
      const hasCleanupBeenDone = sessionStorage.getItem(sessionCleanupKey);
      
      if (!hasCleanupBeenDone) {
        console.log('🧹 First time loading - performing cleanup...');
        await forceCleanupHalls();
        sessionStorage.setItem(sessionCleanupKey, 'true');
        setCleanupDone(true);
      }
      
      // Fetch halls from Firebase
      const hallsData = await FirestoreService.getHalls();
      
      // Additional safety: Remove any duplicates by hall name
      const uniqueHalls = hallsData.filter((hall, index, self) => 
        index === self.findIndex(h => h.name === hall.name)
      );
      
      console.log(`📊 Loaded ${uniqueHalls.length} unique halls out of ${hallsData.length} total`);
      setHalls(uniqueHalls);
    } catch (error) {
      console.error('Error loading halls:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <DashboardLayout>
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Book a Hall</h1>
          <p className="text-gray-600 mt-2">
            Select from our available auditoriums and seminar halls
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {halls.map((hall) => (
            <div key={hall.id} className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-200">
              <h3 className="text-xl font-extrabold text-gray-900 mb-3 tracking-tight">{hall.name}</h3>
                
                <div className="flex items-center space-x-4 mb-4 text-gray-600">
                  <div className="flex items-center space-x-1">
                    <Users className="h-4 w-4" />
                    <span className="text-sm">{hall.capacity} seats</span>
                  </div>
                  <div className="flex items-center space-x-1">
                    <Building className="h-4 w-4" />
                    <span className="text-sm">{hall.facilities.length} facilities</span>
                  </div>
                </div>

                <div className="mb-4">
                  <h4 className="text-sm font-semibold text-gray-800 mb-2">Available Facilities:</h4>
                  <div className="flex flex-wrap gap-2">
                    {hall.facilities.map((facility) => (
                      <span
                        key={facility}
                        className="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full font-medium"
                      >
                        {facility}
                      </span>
                    ))}
                  </div>
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-1">
                    <div className={`w-2 h-2 rounded-full ${hall.available ? 'bg-green-500' : 'bg-red-500'}`}></div>
                    <span className={`text-sm ${hall.available ? 'text-green-600' : 'text-red-600'}`}>
                      {hall.available ? 'Available' : 'Not Available'}
                    </span>
                  </div>
                  
                  <Link
                    to={`/dashboard/book-hall/${hall.id}`}
                    className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                      hall.available
                        ? 'bg-primary text-white hover:bg-primary-dark'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    }`}
                  >
                    Book Now
                  </Link>
                </div>
              </div>
          ))}
        </div>
      </div>
    </DashboardLayout>
  );
}