import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { DashboardLayout } from '../components/DashboardLayout';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { useNotification } from '../components/NotificationToast';
import { User, Phone, Building, FileText, Calendar, CheckCircle, XCircle } from 'lucide-react';
import { PdfViewerModal } from '../components/PdfViewerModal';

export function BookingForm() {
  const { hallId } = useParams();
  const { currentUser } = useAuth();
  const navigate = useNavigate();
  const { showSuccess, showError } = useNotification();
  
  const [hall, setHall] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  
  React.useEffect(() => {
    const fetchHall = async () => {
      if (!hallId) return;
      try {
        setLoading(true);
        const hallData = await FirestoreService.getHallById(hallId);
        if (hallData) {
          setHall(hallData);
          setFormData(prev => ({
            ...prev,
            requiredHall: hallData.name
          }));
        }
      } catch (error) {
        console.error('Error fetching hall:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchHall();
  }, [hallId]);
  
  const [formData, setFormData] = useState({
    applicantName: currentUser?.name || '',
    contactNumber: currentUser?.mobile || '',
    department: currentUser?.department || '',
    requiredHall: '',
    organizingDepartment: '',
    purpose: '',
    seatingCapacity: '',
    facilitiesRequired: [] as string[],
    // From/To with date + time
    fromDate: '',
    fromTime: '',
    toDate: '',
    toTime: ''
  });

  // Keep booking form in sync with the latest user profile.
  // If the user updates their profile, fetch fresh data from Firestore and autofill.
  React.useEffect(() => {
    const syncUserProfileToForm = async () => {
      try {
        if (!currentUser?.uid) return;
        const profile = await FirestoreService.getUserProfile(currentUser.uid);
        if (profile) {
          setFormData(prev => ({
            ...prev,
            applicantName: profile.name || prev.applicantName,
            contactNumber: profile.mobile || prev.contactNumber,
            department: profile.department || prev.department,
            organizingDepartment: prev.organizingDepartment || profile.department || ''
          }));
        }
      } catch (e) {
        console.warn('Could not sync profile to booking form:', e);
      }
    };
    syncUserProfileToForm();
    // Re-run whenever the logged in user changes
  }, [currentUser?.uid]);

  const [availabilityStatus, setAvailabilityStatus] = useState<{
    checking: boolean;
    available: boolean | null;
    message: string;
  }>({ checking: false, available: null, message: '' });

  // PDF upload state
  const [uploading, setUploading] = useState<{ brochure: boolean; approval: boolean }>({ brochure: false, approval: false });
  const [brochureFileName, setBrochureFileName] = useState<string>('');
  const [approvalFileName, setApprovalFileName] = useState<string>('');
  const [eventBrochureLink, setEventBrochureLink] = useState<string>('');
  const [approvalLetterLink, setApprovalLetterLink] = useState<string>('');
  const [pdfModal, setPdfModal] = useState<{ open: boolean; url: string; title: string }>({ open: false, url: '', title: '' });

  // Facilities available for this specific hall (fetched from Firestore)
  const availableFacilities: string[] = Array.isArray(hall?.facilities)
    ? (hall.facilities as string[])
    : [];

  const generateTimeOptions = () => {
    const options: string[] = [];
    for (let hour = 0; hour < 24; hour++) {
      const timeString = `${hour.toString().padStart(2, '0')}:00`;
      options.push(timeString);
    }
    return options;
  };
  const timeOptions = generateTimeOptions();

  // ===== Backend PDF upload helpers (GridFS) =====
  async function uploadPdfToBackend(file: File): Promise<{ url: string }> {
    if (file.type !== 'application/pdf') {
      throw new Error('Only PDF files are allowed');
    }
    const form = new FormData();
    form.append('file', file);
    const res = await fetch(`https://karehallbooking-g695.onrender.com/api/uploads/pdf`, {
      method: 'POST',
      body: form,
    });
    if (!res.ok) {
      const t = await res.text();
      throw new Error(t || 'Upload failed');
    }
    const data = await res.json();
    return { url: data.url };
  }

  const onBrochureChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      setUploading((p) => ({ ...p, brochure: true }));
      setBrochureFileName(file.name);
      const { url } = await uploadPdfToBackend(file);
      setEventBrochureLink(url);
      showSuccess('Uploaded', 'Event brochure uploaded successfully');
    } catch (err: any) {
      showError('Upload Failed', err?.message || 'Failed to upload brochure');
      setBrochureFileName('');
      setEventBrochureLink('');
    } finally {
      setUploading((p) => ({ ...p, brochure: false }));
      e.target.value = '';
    }
  };

  const onApprovalChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      setUploading((p) => ({ ...p, approval: true }));
      setApprovalFileName(file.name);
      const { url } = await uploadPdfToBackend(file);
      setApprovalLetterLink(url);
      showSuccess('Uploaded', 'Approval letter uploaded successfully');
    } catch (err: any) {
      showError('Upload Failed', err?.message || 'Failed to upload approval letter');
      setApprovalFileName('');
      setApprovalLetterLink('');
    } finally {
      setUploading((p) => ({ ...p, approval: false }));
      e.target.value = '';
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleFacilityToggle = (facility: string) => {
    setFormData(prev => ({
      ...prev,
      facilitiesRequired: prev.facilitiesRequired.includes(facility)
        ? prev.facilitiesRequired.filter(f => f !== facility)
        : [...prev.facilitiesRequired, facility]
    }));
  };

  const maybeCheckAvailability = async (next: typeof formData) => {
    const { fromDate, fromTime, toDate, toTime } = next;
    
    console.log('🔍 maybeCheckAvailability called with:', {
      hall: hall?.name,
      fromDate,
      fromTime,
      toDate,
      toTime,
      allFieldsPresent: !!(hall && fromDate && fromTime && toDate && toTime)
    });
    
    if (!hall || !fromDate || !fromTime || !toDate || !toTime) {
      console.log('❌ Missing required fields, clearing availability status');
      setAvailabilityStatus({ checking: false, available: null, message: '' });
      return;
    }
    
    console.log('✅ All fields present, starting availability check...');
    setAvailabilityStatus(prev => ({ ...prev, checking: true }));
    
    try {
      console.log('🔍 DEBUG: Starting availability check with:', {
        hallName: hall.name,
        fromDate,
        fromTime,
        toDate,
        toTime
      });
      
      // First, let's debug what bookings exist
      await FirestoreService.debugHallBookings(hall.name);
      
    const res = await FirestoreService.checkHallAvailabilityRange(hall.name, fromDate, fromTime, toDate, toTime);
      console.log('🔍 DEBUG: Availability result:', res);
      
    setAvailabilityStatus({
      checking: false,
      available: res.available,
      message: res.available
        ? `✅ Available from ${fromDate} ${fromTime} to ${toDate} ${toTime}.`
        : `❌ Not available. ${res.reason || ''}`
    });
    } catch (error) {
      console.error('Error checking availability:', error);
      setAvailabilityStatus({
        checking: false,
        available: false,
        message: '❌ Error checking availability. Please try again.'
      });
    }
  };

  // Debug function to test availability manually
  const testAvailability = async () => {
    if (!hall) {
      console.log('❌ No hall selected');
      return;
    }
    
    console.log('🧪 Testing availability manually...');
    console.log('Hall:', hall.name);
    console.log('Form data:', formData);
    
    try {
      await FirestoreService.debugHallBookings(hall.name);
      const result = await FirestoreService.checkHallAvailabilityRange(
        hall.name, 
        formData.fromDate, 
        formData.fromTime, 
        formData.toDate, 
        formData.toTime
      );
      console.log('🧪 Test result:', result);
    } catch (error) {
      console.error('🧪 Test error:', error);
    }
  };

  // Test the exact pending booking scenario
  const testPendingBooking = async () => {
    console.log('🧪 Testing pending booking scenario...');
    try {
      const result = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '09:00',
        '2025-10-20',
        '18:00'
      );
      console.log('🧪 Pending booking test result:', result);
    } catch (error) {
      console.error('🧪 Pending booking test error:', error);
    }
  };

  // Test the exact scenario from the user's data
  const testExactScenario = async () => {
    console.log('🧪 Testing exact scenario from user data...');
    console.log('Expected: Hall should be NOT available for 2025-10-20 09:00-18:00 due to pending booking');
    
    try {
      // Test the exact same time (should be blocked)
      const result1 = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '09:00',
        '2025-10-20',
        '18:00'
      );
      console.log('🧪 Same time test (should be blocked):', result1);
      
      // Test overlapping time (should be blocked)
      const result2 = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '10:00',
        '2025-10-20',
        '17:00'
      );
      console.log('🧪 Overlapping time test (should be blocked):', result2);
      
      // Test different time (should be available)
      const result3 = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-21',
        '09:00',
        '2025-10-21',
        '18:00'
      );
      console.log('🧪 Different date test (should be available):', result3);
      
    } catch (error) {
      console.error('🧪 Exact scenario test error:', error);
    }
  };

  // Force test the exact scenario with the user's data
  const forceTestExactScenario = async () => {
    console.log('🧪 FORCE TESTING exact scenario...');
    console.log('User data shows:');
    console.log('- hallName: "K. S. Krishnan Auditorium"');
    console.log('- dates: ["2025-10-20"]');
    console.log('- timeFrom: "09:00"');
    console.log('- timeTo: "18:00"');
    console.log('- status: "pending"');
    console.log('Expected: Should be BLOCKED');
    
    try {
      // Then test availability
      const result = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '09:00',
        '2025-10-20',
        '18:00'
      );
      
      console.log('🧪 FORCE TEST RESULT:', result);
      
      if (result.available) {
        console.error('❌ ERROR: Should be blocked but showing as available!');
        console.error('❌ This means the availability check is not working properly!');
      } else {
        console.log('✅ SUCCESS: Correctly blocked');
      }
      
    } catch (error) {
      console.error('🧪 FORCE TEST ERROR:', error);
    }
  };

  // Simple test that should definitely work
  const simpleTest = async () => {
    console.log('🧪 SIMPLE TEST - Testing with exact user data...');
    
    try {
      const result = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '09:00',
        '2025-10-20',
        '18:00'
      );
      
      console.log('🧪 SIMPLE TEST RESULT:', result);
      
      if (result.available) {
        console.error('❌❌❌ CRITICAL ERROR: Hall should be BLOCKED but showing as AVAILABLE!');
        console.error('❌ This means there is a fundamental issue with the availability check!');
      } else {
        console.log('✅✅✅ SUCCESS: Hall correctly blocked!');
      }
      
    } catch (error) {
      console.error('🧪 SIMPLE TEST ERROR:', error);
    }
  };

  // Test multiple bookings per day scenario
  const testMultipleBookingsPerDay = async () => {
    console.log('🧪 TESTING MULTIPLE BOOKINGS PER DAY SCENARIO...');
    console.log('Scenario: Morning booking 9AM-5PM, Evening booking 6PM-9PM');
    
    try {
      // Test 1: Morning booking (should be blocked due to existing 9AM-6PM booking)
      console.log('📅 Test 1: Morning booking 9AM-5PM (should be blocked)');
      const morningResult = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '09:00',
        '2025-10-20',
        '17:00'
      );
      console.log('🧪 Morning booking result:', morningResult);
      
      // Test 2: Evening booking (should be available - no overlap with 9AM-6PM)
      console.log('📅 Test 2: Evening booking 6PM-9PM (should be available)');
      const eveningResult = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '18:00',
        '2025-10-20',
        '21:00'
      );
      console.log('🧪 Evening booking result:', eveningResult);
      
      // Test 3: Overlapping booking (should be blocked)
      console.log('📅 Test 3: Overlapping booking 4PM-7PM (should be blocked)');
      const overlapResult = await FirestoreService.checkHallAvailabilityRange(
        'K. S. Krishnan Auditorium',
        '2025-10-20',
        '16:00',
        '2025-10-20',
        '19:00'
      );
      console.log('🧪 Overlapping booking result:', overlapResult);
      
      console.log('📊 SUMMARY:');
      console.log(`Morning (9AM-5PM): ${morningResult.available ? 'AVAILABLE' : 'BLOCKED'} - ${morningResult.reason || 'No conflicts'}`);
      console.log(`Evening (6PM-9PM): ${eveningResult.available ? 'AVAILABLE' : 'BLOCKED'} - ${eveningResult.reason || 'No conflicts'}`);
      console.log(`Overlap (4PM-7PM): ${overlapResult.available ? 'AVAILABLE' : 'BLOCKED'} - ${overlapResult.reason || 'No conflicts'}`);
      
    } catch (error) {
      console.error('🧪 Multiple bookings test error:', error);
    }
  };

  // Make test functions available globally for debugging
  React.useEffect(() => {
    (window as any).testAvailability = testAvailability;
    (window as any).testPendingBooking = testPendingBooking;
    (window as any).testExactScenario = testExactScenario;
    (window as any).forceTestExactScenario = forceTestExactScenario;
    (window as any).simpleTest = simpleTest;
    (window as any).testMultipleBookingsPerDay = testMultipleBookingsPerDay;
  }, [hall, formData]);

  const onDateChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    console.log(`📅 Date changed: ${name} = ${value}`);
    const next = { ...formData, [name]: value } as typeof formData;
    setFormData(next);
    await maybeCheckAvailability(next);
  };

  const onTimeChange = async (e: React.ChangeEvent<HTMLSelectElement>) => {
    const { name, value } = e.target;
    console.log(`⏰ Time changed: ${name} = ${value}`);
    const next = { ...formData, [name]: value } as typeof formData;
    setFormData(next);
    await maybeCheckAvailability(next);
  };

  const getMinDate = (): string => {
    const today = new Date();
    return today.toISOString().split('T')[0];
  };
  const getMaxDate = (): string => {
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);
    return maxDate.toISOString().split('T')[0];
  };

  const validateDateTimeRange = (): { isValid: boolean; message: string } => {
    const { fromDate, fromTime, toDate, toTime } = formData;
    if (!fromDate || !fromTime || !toDate || !toTime) return { isValid: false, message: 'Please select all date and time fields.' };

    const start = new Date(fromDate);
    const end = new Date(toDate);
    const today = new Date(); today.setHours(0,0,0,0);
    if (start < today) return { isValid: false, message: 'From date cannot be in the past.' };
    if (end < start) return { isValid: false, message: 'To date cannot be before From date.' };

    if (fromDate === toDate && fromTime >= toTime) {
      return { isValid: false, message: 'End time must be after start time for the same day.' };
    }
    
    return { isValid: true, message: '' };
  };

  const validateRequiredFields = (): { isValid: boolean; message: string } => {
    const { organizingDepartment, purpose, seatingCapacity } = formData;
    if (!organizingDepartment.trim()) return { isValid: false, message: 'Please enter the organizing department.' };
    if (!purpose.trim()) return { isValid: false, message: 'Please enter the purpose of the hall.' };
    if (!seatingCapacity || parseInt(seatingCapacity) <= 0) return { isValid: false, message: 'Please enter a valid seating capacity.' };
    if (hall && parseInt(seatingCapacity) > hall.capacity) return { isValid: false, message: `Please enter seating capacity within the hall's limit. Maximum capacity is ${hall.capacity} seats.` };
    return { isValid: true, message: '' };
  };

  const buildDateArray = (fromDate: string, toDate: string): string[] => {
    const dates: string[] = [];
    const start = new Date(fromDate);
    const end = new Date(toDate);
    for (let d = new Date(start); d.getTime() <= end.getTime(); d.setDate(d.getDate() + 1)) {
      dates.push(d.toISOString().split('T')[0]);
    }
    return dates;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!currentUser || !hall) { 
      showError('Authentication Error', 'User not authenticated or hall not found.'); 
      return; 
    }

    const requiredOk = validateRequiredFields();
    if (!requiredOk.isValid) { 
      showError('Validation Error', requiredOk.message); 
      return; 
    }

    const rangeOk = validateDateTimeRange();
    if (!rangeOk.isValid) { 
      showError('Date/Time Error', rangeOk.message); 
      return; 
    }

    if (availabilityStatus.available === false) { 
      showError('Availability Error', 'This hall is not available for the selected range.'); 
      return; 
    }
    
    setSubmitting(true);
    try {
      const dates = buildDateArray(formData.fromDate, formData.toDate);
      const bookingData = {
        userId: currentUser.uid,
        userName: formData.applicantName,
        userEmail: currentUser.email || '',
        userMobile: formData.contactNumber,
        userDesignation: 'Student',
        userDepartment: formData.department,
        department: formData.department,
        hallId: hall.id,
        hallName: formData.requiredHall,
        organizingDepartment: formData.organizingDepartment,
        purpose: formData.purpose,
        seatingCapacity: parseInt(formData.seatingCapacity),
        facilities: formData.facilitiesRequired,
        facilitiesRequired: formData.facilitiesRequired,
        dates,
        timeFrom: formData.fromTime,
        timeTo: formData.toTime,
        numberOfDays: dates.length,
        status: 'pending' as const,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
        // New PDF links
        eventBrochureLink,
        approvalLetterLink
      };
      await FirestoreService.createBooking(bookingData);
      setShowSuccessModal(true);
      setTimeout(() => navigate('/dashboard'), 3000);
    } catch (err) {
      console.error('Error submitting booking:', err);
      showError('Submission Failed', 'Failed to submit booking request. Please try again.');
    } finally {
      setSubmitting(false);
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

  if (!hall) {
    return (
      <DashboardLayout>
        <div className="text-center py-12">
          <h2 className="text-2xl font-bold text-gray-800">Hall not found</h2>
          <p className="text-gray-600 mt-2">The requested hall could not be found.</p>
        </div>
      </DashboardLayout>
    );
  }

  const capacityNumber = parseInt(formData.seatingCapacity || '0');
  const overCapacity = hall && capacityNumber > hall.capacity;
  const validCapacity = hall && capacityNumber > 0 && capacityNumber <= hall.capacity;

  return (
    <DashboardLayout>
      <div className="max-w-4xl mx-auto space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Book {hall.name}</h1>
          <p className="text-gray-600 mt-2">Fill out the form below to request a booking</p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center space-x-4 mb-6">
            {/* Removed hall image as requested */}
            <div>
              <h3 className="text-xl font-bold text-gray-800">{hall.name}</h3>
              <p className="text-gray-600">Capacity: {hall.capacity} seats</p>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            {(availabilityStatus.checking || availabilityStatus.message) && (
              <div className={`p-6 rounded-lg border-2 text-center ${availabilityStatus.checking ? 'bg-blue-50 border-blue-300 text-blue-800' : availabilityStatus.available ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800'}`}>
                <div className="flex items-center justify-center space-x-3">
                  {availabilityStatus.checking ? (
                    <>
                      <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                      <span className="text-lg font-semibold">Checking availability...</span>
                    </>
                  ) : availabilityStatus.available ? (
                    <>
                      <CheckCircle className="h-8 w-8" />
                      <span className="text-lg font-semibold">{availabilityStatus.message}</span>
                    </>
                  ) : (
                    <>
                      <XCircle className="h-8 w-8" />
                      <span className="text-lg font-semibold">{availabilityStatus.message}</span>
                    </>
                  )}
                </div>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Applicant Name</label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input type="text" name="applicantName" value={formData.applicantName} readOnly aria-readonly="true" className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700" />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                <div className="relative">
                  <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input type="tel" name="contactNumber" value={formData.contactNumber} readOnly aria-readonly="true" className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700" />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Department/Institution</label>
                <div className="relative">
                  <Building className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input type="text" name="department" value={formData.department} readOnly aria-readonly="true" className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700" />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Required Hall</label>
                <input type="text" name="requiredHall" value={formData.requiredHall} readOnly className="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Organizing Department/Institution</label>
                <input type="text" name="organizingDepartment" value={formData.organizingDepartment} onChange={handleInputChange} className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Seating Capacity Required</label>
                <input type="number" name="seatingCapacity" value={formData.seatingCapacity} onChange={handleInputChange} max={hall.capacity} className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent ${overCapacity ? 'border-red-300 bg-red-50' : 'border-gray-300'}`} required />
                {overCapacity && (
                  <p className="mt-2 text-sm text-red-600 flex items-center"><XCircle className="h-4 w-4 mr-1" />Please enter seating capacity within the hall's limit. Maximum capacity is {hall.capacity} seats.</p>
                )}
                {validCapacity && (
                  <p className="mt-2 text-sm text-green-600 flex items-center"><CheckCircle className="h-4 w-4 mr-1" />Capacity is within the hall's limit ({hall.capacity} seats).</p>
                )}
              </div>
            </div>

            {/* Facilities Required */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Facilities Required</label>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {availableFacilities.map(facility => (
                  <label key={facility} className="flex items-center space-x-2">
                    <input
                      type="checkbox"
                      checked={formData.facilitiesRequired.includes(facility)}
                      onChange={() => handleFacilityToggle(facility)}
                      className="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded"
                    />
                    <span className="text-gray-700">{facility}</span>
                  </label>
                ))}
              </div>
            </div>

            {/* From / To with date + time */}
            <div className="mb-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <p className="text-sm font-medium text-gray-700 mb-2">⏰ From</p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                    <div className="relative">
                      <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                      <input type="date" name="fromDate" value={formData.fromDate} onChange={onDateChange} min={getMinDate()} max={getMaxDate()} className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required />
                    </div>
                    <select name="fromTime" value={formData.fromTime} onChange={onTimeChange} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm" required>
                      <option value="">Select time</option>
                      {timeOptions.map(time => (<option key={time} value={time}>{time}</option>))}
                    </select>
                  </div>
                </div>

                <div>
                  <p className="text-sm font-medium text-gray-700 mb-2">⏰ To</p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                    <div className="relative">
                      <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                      <input type="date" name="toDate" value={formData.toDate} onChange={onDateChange} min={formData.fromDate || getMinDate()} max={getMaxDate()} className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required />
                    </div>
                    <select name="toTime" value={formData.toTime} onChange={onTimeChange} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm" required>
                      <option value="">Select time</option>
                      {timeOptions.map(time => (<option key={time} value={time}>{time}</option>))}
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Purpose of the Hall</label>
              <div className="relative">
                <FileText className="absolute left-3 top-3 h-5 w-5 text-gray-400" />
                <textarea name="purpose" value={formData.purpose} onChange={handleInputChange} rows={4} className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Describe the purpose of booking this hall..." required />
              </div>
            </div>

            {/* PDF uploads row */}
            <div className="mb-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Event Brochure */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Event Brochure (PDF)</label>
                  <input
                    type="file"
                    accept=".pdf"
                    onChange={onBrochureChange}
                    className="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 hover:file:bg-gray-200"
                  />
                  {uploading.brochure && <p className="mt-2 text-sm text-gray-500">Uploading…</p>}
                  {!!eventBrochureLink && (
                    <p className="mt-2 text-sm text-green-700">
                      <button
                        type="button"
                        onClick={() => setPdfModal({ open: true, url: eventBrochureLink, title: brochureFileName || 'Event Brochure' })}
                        className="underline hover:text-green-800"
                      >
                        {brochureFileName}
                      </button>
                      <span className="ml-1">✅ Uploaded</span>
                    </p>
                  )}
                </div>

                {/* Approval Letter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Approval Letter (PDF)</label>
                  <input
                    type="file"
                    accept=".pdf"
                    onChange={onApprovalChange}
                    className="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 hover:file:bg-gray-200"
                  />
                  {uploading.approval && <p className="mt-2 text-sm text-gray-500">Uploading…</p>}
                  {!!approvalLetterLink && (
                    <p className="mt-2 text-sm text-green-700">
                      <button
                        type="button"
                        onClick={() => setPdfModal({ open: true, url: approvalLetterLink, title: approvalFileName || 'Approval Letter' })}
                        className="underline hover:text-green-800"
                      >
                        {approvalFileName}
                      </button>
                      <span className="ml-1">✅ Uploaded</span>
                    </p>
                  )}
                </div>
              </div>
            </div>

            <div className="flex justify-end space-x-4">
              <button type="button" onClick={() => navigate('/dashboard/book-hall')} className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
              <button type="submit" disabled={submitting || availabilityStatus.available === false} className={`px-6 py-3 rounded-lg transition-colors font-medium ${submitting || availabilityStatus.available === false ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-primary text-white hover:bg-primary-dark'}`}>{submitting ? 'Submitting...' : 'Submit Booking Request'}</button>
            </div>
          </form>
        </div>
      </div>

      {showSuccessModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-2xl p-8 max-w-md mx-4 shadow-2xl transform transition-all duration-300 scale-100">
            <div className="text-center">
              <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6"><CheckCircle className="h-10 w-10 text-green-600" /></div>
              <h3 className="text-2xl font-bold text-gray-900 mb-4">Booking Submitted Successfully!</h3>
              <p className="text-gray-600 mb-6 leading-relaxed">Thank you for your booking request. Please wait for admin approval. You will be notified once your booking is reviewed.</p>
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button onClick={() => { setShowSuccessModal(false); navigate('/dashboard'); }} className="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl">Go to Dashboard</button>
                <button onClick={() => setShowSuccessModal(false)} className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors">Stay Here</button>
              </div>
              <p className="text-sm text-gray-500 mt-4">Redirecting to dashboard in 3 seconds...</p>
            </div>
          </div>
        </div>
      )}

      {/* PDF modal viewer */}
      <PdfViewerModal
        isOpen={pdfModal.open}
        onClose={() => setPdfModal({ open: false, url: '', title: '' })}
        url={pdfModal.url}
        title={pdfModal.title}
      />
    </DashboardLayout>
  );
}