import React, { useState, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { User, Mail, Phone, GraduationCap, Building, Edit2, Save, Home, MapPin, CheckCircle } from 'lucide-react';

export function Profile() {
  const { currentUser, firebaseToken } = useAuth();
  const [isEditing, setIsEditing] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [originalData, setOriginalData] = useState({
    name: currentUser?.name || '',
    email: currentUser?.email || '',
    mobile: currentUser?.mobile || '',
    designation: 'Faculty',
    department: currentUser?.department || '',
    cabinNumber: currentUser?.cabinNumber || '',
    officeLocation: currentUser?.officeLocation || ''
  });
  const [formData, setFormData] = useState({
    name: currentUser?.name || '',
    email: currentUser?.email || '',
    mobile: currentUser?.mobile || '',
    designation: 'Faculty',
    department: currentUser?.department || '',
    cabinNumber: currentUser?.cabinNumber || '',
    officeLocation: currentUser?.officeLocation || ''
  });

  // Load saved data from backend API on component mount
  useEffect(() => {
    const loadProfileData = async () => {
      if (currentUser?.uid) {
        try {
          const response = await fetch('/api/auth/me', {
            headers: {
              'Authorization': `Bearer ${firebaseToken || ''}`
            }
          });

          if (response.ok) {
            const result = await response.json();
            if (result.success && result.data.user) {
              const savedData = result.data.user;
              const userData = {
                name: savedData.name || currentUser.name || '',
                email: savedData.email || currentUser.email || '',
                mobile: savedData.mobile || currentUser.mobile || '',
                designation: savedData.designation || 'Faculty',
                department: savedData.department || currentUser.department || '',
                cabinNumber: savedData.cabinNumber || '',
                officeLocation: savedData.officeLocation || ''
              };
              setFormData(userData);
              setOriginalData(userData);
            } else {
              throw new Error('Failed to load profile data');
            }
          } else {
            throw new Error('Failed to load profile data');
          }
        } catch (error) {
          console.error('Error loading profile data:', error);
          // Fallback to current user data
          const userData = {
            name: currentUser.name || '',
            email: currentUser.email || '',
            mobile: currentUser.mobile || '',
            designation: 'Faculty',
            department: currentUser.department || '',
            cabinNumber: '',
            officeLocation: ''
          };
          setFormData(userData);
          setOriginalData(userData);
        }
      }
    };

    loadProfileData();
  }, [currentUser, firebaseToken]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSave = async () => {
    if (!currentUser?.uid) {
      console.error('No user ID available');
      return;
    }

    setIsSaving(true);
    try {
      // Save to backend API
      const response = await fetch('/api/auth/profile', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${firebaseToken || ''}`
        },
        body: JSON.stringify(formData)
      });

      if (!response.ok) {
        throw new Error('Failed to save profile');
      }

      const result = await response.json();
      
      if (result.success) {
        // Save the current form data as the new original data
        setOriginalData(formData);
        setShowSuccessModal(true);
        setIsEditing(false);
      } else {
        throw new Error(result.message || 'Failed to save profile');
      }
    } catch (error) {
      console.error('Error saving profile:', error);
      alert('Failed to save profile. Please try again.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    // Revert form data to the last saved state
    setFormData(originalData);
    setIsEditing(false);
  };

  // Auto-close modal after 2 seconds
  useEffect(() => {
    if (showSuccessModal) {
      const timer = setTimeout(() => {
        setShowSuccessModal(false);
      }, 2000);
      return () => clearTimeout(timer);
    }
  }, [showSuccessModal]);

  // Removed designations array since it's fixed as Faculty

  return (
    <DashboardLayout>
      <div className="max-w-4xl mx-auto space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-800">Profile Settings</h1>
            <p className="text-gray-600 mt-2">Manage your account information</p>
          </div>
          <button
            onClick={() => setIsEditing(!isEditing)}
            className="flex items-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
          >
            {isEditing ? <Save className="h-4 w-4" /> : <Edit2 className="h-4 w-4" />}
            <span>{isEditing ? 'Save Changes' : 'Edit Profile'}</span>
          </button>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center space-x-6 mb-8">
            <div className="w-24 h-24 bg-primary rounded-full flex items-center justify-center">
              <User className="h-12 w-12 text-white" />
            </div>
            <div>
              <h3 className="text-2xl font-bold text-gray-800">{currentUser?.name}</h3>
              <p className="text-gray-600">Faculty</p>
              <span className={`inline-block px-3 py-1 text-sm rounded-full mt-2 ${
                currentUser?.role === 'admin' 
                  ? 'bg-accent text-white' 
                  : 'bg-primary text-white'
              }`}>
                {currentUser?.role === 'admin' ? 'Administrator' : 'Faculty'}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Full Name
              </label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Email Address
              </label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Mobile Number
              </label>
              <div className="relative">
                <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="tel"
                  name="mobile"
                  value={formData.mobile}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Designation
              </label>
              <div className="relative">
                <GraduationCap className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  name="designation"
                  value={formData.designation}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Department/Institution
              </label>
              <div className="relative">
                <Building className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <select
                  name="department"
                  value={formData.department}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg appearance-none ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                >
                  <option value="">Select Department</option>
                  <option value="CSE">CSE</option>
                  <option value="ECE">ECE</option>
                  <option value="IT">IT</option>
                  <option value="CIVIL">CIVIL</option>
                  <option value="MECHANICAL">MECHANICAL</option>
                  <option value="AERONAUTICAL">AERONAUTICAL</option>
                  <option value="POLYTECHNIC">POLYTECHNIC</option>
                </select>
                <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Cabin / Room Number
              </label>
              <div className="relative">
                <Home className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  name="cabinNumber"
                  value={formData.cabinNumber}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  placeholder="e.g., A-101, Room 205"
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Office Location / Block Name
              </label>
              <div className="relative">
                <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  name="officeLocation"
                  value={formData.officeLocation}
                  onChange={handleInputChange}
                  disabled={!isEditing}
                  placeholder="e.g., Computer Science Block, Admin Block"
                  className={`w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg ${
                    isEditing 
                      ? 'focus:ring-2 focus:ring-primary focus:border-transparent' 
                      : 'bg-gray-50'
                  }`}
                />
              </div>
            </div>
          </div>

          {isEditing && (
            <div className="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
              <button
                onClick={handleCancel}
                className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleSave}
                disabled={isSaving}
                className="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSaving ? 'Saving...' : 'Save Changes'}
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Success Modal */}
      {showSuccessModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-2xl p-8 max-w-md mx-4 shadow-2xl transform transition-all duration-300 scale-100">
            <div className="text-center">
              <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <CheckCircle className="h-10 w-10 text-green-600" />
              </div>
              <h3 className="text-2xl font-bold text-gray-900 mb-4">Profile Updated Successfully!</h3>
              <p className="text-gray-600 mb-6 leading-relaxed">
                Thank you for updating your profile. Your information has been saved successfully.
              </p>
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button 
                  onClick={() => setShowSuccessModal(false)} 
                  className="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                >
                  Continue
                </button>
              </div>
              <p className="text-sm text-gray-500 mt-4">This dialog will close automatically in 2 seconds...</p>
            </div>
          </div>
        </div>
      )}
    </DashboardLayout>
  );
}