import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { DashboardLayout } from '../components/DashboardLayout';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { User, Phone, Building, FileText, Users, Calendar, Clock, CheckCircle, XCircle } from 'lucide-react';

export function BookingForm() {
  const { hallId } = useParams();
  const { currentUser } = useAuth();
  const navigate = useNavigate();
  
  const [hall, setHall] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  
  // Fetch hall data on component mount
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
    requiredHall: hall?.name || '',
    organizingDepartment: '',
    purpose: '',
    seatingCapacity: '',
    facilitiesRequired: [] as string[],
    numberOfDays: 1,
    startDate: '',
    endDate: '',
    eventDate: '',
    timeFrom: '',
    timeTo: '',
    // New fields for enhanced time selection
    checkInDate: '',
    checkInTime: '',
    checkOutDate: '',
    checkOutTime: ''
  });

  const [availabilityStatus, setAvailabilityStatus] = useState<{
    checking: boolean;
    available: boolean | null;
    message: string;
  }>({
    checking: false,
    available: null,
    message: ''
  });

  // Check availability when check-in date changes for multi-day bookings
  React.useEffect(() => {
    if (formData.checkInDate && hall && formData.numberOfDays > 1) {
      checkHallAvailability(hall.name, formData.checkInDate);
    }
  }, [formData.checkInDate, formData.numberOfDays, hall]);

  const availableFacilities = ['Reception', 'Audio', 'Power Backup'];

  // Time picker options (hourly intervals)

  // Generate time options for dropdown (hourly only)
  const generateTimeOptions = () => {
    const options = [];
    for (let hour = 0; hour < 24; hour++) {
      const timeString = `${hour.toString().padStart(2, '0')}:00`;
      options.push(timeString);
    }
    return options;
  };

  const timeOptions = generateTimeOptions();

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFacilityChange = (facility: string) => {
    setFormData(prev => ({
      ...prev,
      facilitiesRequired: prev.facilitiesRequired.includes(facility)
        ? prev.facilitiesRequired.filter(f => f !== facility)
        : [...prev.facilitiesRequired, facility]
    }));
  };

  const handleDateChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    
    // Reset availability status when date changes
    setAvailabilityStatus({
      checking: false,
      available: null,
      message: ''
    });
    
    setFormData(prev => {
      const newData = {
        ...prev,
        [name]: value
      };

      // Auto-fill check-out date for multi-day bookings
      if (name === 'checkInDate' && value && prev.numberOfDays > 1) {
        const checkInDate = new Date(value);
        const checkOutDate = new Date(checkInDate);
        checkOutDate.setDate(checkInDate.getDate() + (prev.numberOfDays - 1));
        
        newData.checkOutDate = checkOutDate.toISOString().split('T')[0];
        console.log('Auto-filling check-out date:', newData.checkOutDate, 'for', prev.numberOfDays, 'days');
      }

      return newData;
    });

    // Check availability if date is selected and hall is available
    if (value && hall) {
      // For multi-day bookings, check availability for the check-in date
      const dateToCheck = name === 'checkInDate' ? value : value;
      checkHallAvailability(hall.name, dateToCheck);
    }
  };

  const handleNumberOfDaysChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const days = parseInt(e.target.value);
    
    setFormData(prev => {
      const newData = {
        ...prev,
        numberOfDays: days,
        // Reset dates when changing number of days
        startDate: '',
        endDate: '',
        eventDate: '',
        checkInDate: '',
        checkInTime: '',
        checkOutDate: '',
        checkOutTime: '',
        timeFrom: '',
        timeTo: ''
      };

      // If there's already a check-in date and it's multi-day, auto-fill check-out date
      if (prev.checkInDate && days > 1) {
        newData.checkInDate = prev.checkInDate;
        const checkInDate = new Date(prev.checkInDate);
        const checkOutDate = new Date(checkInDate);
        checkOutDate.setDate(checkInDate.getDate() + (days - 1));
        newData.checkOutDate = checkOutDate.toISOString().split('T')[0];
      }

      return newData;
    });

    // Reset availability status
    setAvailabilityStatus({
      checking: false,
      available: null,
      message: ''
    });
  };

  const checkHallAvailability = async (hallName: string, date: string) => {
    setAvailabilityStatus(prev => ({ ...prev, checking: true }));

    try {
      console.log('Checking availability for:', { hallName, date });
      // Use real Firebase availability check
      const response = await FirestoreService.checkHallAvailability(hallName, date);
      console.log('Availability response:', response);
      
      setAvailabilityStatus({
        checking: false,
        available: response.available,
        message: response.available 
          ? `✅ This hall is available on ${formatDate(date)}.`
          : `❌ This hall is not available on ${formatDate(date)}. ${response.reason || 'Please choose another date.'}`
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


  const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      day: 'numeric', 
      month: 'short', 
      year: 'numeric' 
    });
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

  const validateDates = (): { isValid: boolean; message: string } => {
    const { numberOfDays, eventDate, checkInDate, checkOutDate } = formData;
    
    if (numberOfDays === 1) {
      if (!eventDate) {
        return { isValid: false, message: 'Please select an event date.' };
      }
    } else {
      // Use new check-in/check-out fields for multi-day
      if (!checkInDate || !checkOutDate) {
        return { isValid: false, message: 'Please select both check-in and check-out dates.' };
      }
      
      const start = new Date(checkInDate);
      const end = new Date(checkOutDate);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (start < today) {
        return { isValid: false, message: 'Check-in date cannot be in the past.' };
      }
      
      // Check if same date is selected for both check-in and check-out
      if (checkInDate === checkOutDate) {
        return { isValid: false, message: 'Please select another date for check-out. Multi-day booking requires different dates.' };
      }
      
      if (end < start) {
        return { isValid: false, message: 'Check-out date must be after check-in date.' };
      }
      
      // Check if the date range matches the number of days
      const diffTime = Math.abs(end.getTime() - start.getTime());
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
      
      if (diffDays !== numberOfDays) {
        return { isValid: false, message: `Date range should be exactly ${numberOfDays} day${numberOfDays > 1 ? 's' : ''}.` };
      }
    }
    
    return { isValid: true, message: '' };
  };

  const validateTimes = (): { isValid: boolean; message: string } => {
    const { numberOfDays, timeFrom, timeTo, checkInTime, checkOutTime } = formData;
    
    if (numberOfDays === 1) {
      if (!timeFrom || !timeTo) {
        return { isValid: false, message: 'Please select both start and end times.' };
      }
      
      if (timeFrom >= timeTo) {
        return { isValid: false, message: 'End time must be after start time.' };
      }
    } else {
      if (!checkInTime || !checkOutTime) {
        return { isValid: false, message: 'Please select both check-in and check-out times.' };
      }
      
      // For multi-day, we only validate if it's the same day
      if (formData.checkInDate === formData.checkOutDate && checkInTime >= checkOutTime) {
        return { isValid: false, message: 'Check-out time must be after check-in time for same-day bookings.' };
      }
    }
    
    return { isValid: true, message: '' };
  };

  const validateRequiredFields = (): { isValid: boolean; message: string } => {
    const { organizingDepartment, purpose, seatingCapacity } = formData;
    
    if (!organizingDepartment.trim()) {
      return { isValid: false, message: 'Please enter the organizing department.' };
    }
    
    if (!purpose.trim()) {
      return { isValid: false, message: 'Please enter the purpose of the hall.' };
    }
    
    if (!seatingCapacity || parseInt(seatingCapacity) <= 0) {
      return { isValid: false, message: 'Please enter a valid seating capacity.' };
    }
    
    // Check if seating capacity exceeds hall capacity
    if (hall && parseInt(seatingCapacity) > hall.capacity) {
      return { isValid: false, message: `Please enter seating capacity within the hall's limit. Maximum capacity is ${hall.capacity} seats.` };
    }
    
    return { isValid: true, message: '' };
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    console.log('=== SUBMIT FUNCTION CALLED ===');
    console.log('Submit clicked, form data:', formData);
    console.log('Availability status:', availabilityStatus);
    
    if (!currentUser || !hall) {
      alert('User not authenticated or hall not found.');
      return;
    }
    
    // Validate required fields
    const requiredFieldsValidation = validateRequiredFields();
    console.log('Required fields validation:', requiredFieldsValidation);
    if (!requiredFieldsValidation.isValid) {
      alert(requiredFieldsValidation.message);
      return;
    }
    
    // Validate dates
    const dateValidation = validateDates();
    console.log('Date validation:', dateValidation);
    if (!dateValidation.isValid) {
      alert(dateValidation.message);
      return;
    }
    
    // Validate times
    const timeValidation = validateTimes();
    console.log('Time validation:', timeValidation);
    if (!timeValidation.isValid) {
      alert(timeValidation.message);
      return;
    }
    
    // Check if hall is available before submitting (only if explicitly unavailable)
    console.log('Availability check:', availabilityStatus.available);
    if (availabilityStatus.available === false) {
      alert('This hall is not available on the selected date. Please choose another hall or date.');
      return;
    }
    
    setSubmitting(true);

    try {
      // Generate dates array based on number of days
      let dates: string[] = [];
      
      if (formData.numberOfDays === 1) {
        dates = [formData.eventDate];
      } else {
        const start = new Date(formData.checkInDate);
        const end = new Date(formData.checkOutDate);
        
        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
          dates.push(d.toISOString().split('T')[0]);
        }
      }

      // Create booking data
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
        dates: dates,
        timeFrom: formData.numberOfDays === 1 ? formData.timeFrom : formData.checkInTime,
        timeTo: formData.numberOfDays === 1 ? formData.timeTo : formData.checkOutTime,
        // Enhanced fields for multi-day bookings
        checkInDate: formData.numberOfDays > 1 ? formData.checkInDate : undefined,
        checkInTime: formData.numberOfDays > 1 ? formData.checkInTime : undefined,
        checkOutDate: formData.numberOfDays > 1 ? formData.checkOutDate : undefined,
        checkOutTime: formData.numberOfDays > 1 ? formData.checkOutTime : undefined,
        numberOfDays: formData.numberOfDays,
        status: 'pending' as const,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      };

      // Submit to Firebase
      await FirestoreService.createBooking(bookingData);
      
      // Show success modal
      setShowSuccessModal(true);
      
      // Navigate to dashboard after 3 seconds
      setTimeout(() => {
        navigate('/dashboard');
      }, 3000);
    } catch (error) {
      console.error('Error submitting booking:', error);
      alert('Failed to submit booking request. Please try again.');
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

  return (
    <DashboardLayout>
      <div className="max-w-4xl mx-auto space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Book {hall.name}</h1>
          <p className="text-gray-600 mt-2">Fill out the form below to request a booking</p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center space-x-4 mb-6">
            <img
              src={hall.image}
              alt={hall.name}
              className="w-24 h-24 object-cover rounded-lg"
            />
            <div>
              <h3 className="text-xl font-bold text-gray-800">{hall.name}</h3>
              <p className="text-gray-600">Capacity: {hall.capacity} seats</p>
              <div className="flex flex-wrap gap-2 mt-2">
                {hall.facilities.map((facility: string) => (
                  <span
                    key={facility}
                    className="px-2 py-1 text-xs bg-primary text-white rounded-full"
                  >
                    {facility}
                  </span>
                ))}
              </div>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Availability Status Banner - Prominent Display */}
            {(availabilityStatus.checking || availabilityStatus.message) && (
              <div className={`p-6 rounded-lg border-2 text-center ${
                availabilityStatus.checking 
                  ? 'bg-blue-50 border-blue-300 text-blue-800'
                  : availabilityStatus.available 
                  ? 'bg-green-50 border-green-300 text-green-800'
                  : 'bg-red-50 border-red-300 text-red-800'
              }`}>
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
                {!availabilityStatus.available && !availabilityStatus.checking && (
                  <p className="text-red-600 mt-3 text-sm">
                    This hall is not available on the selected date. Please choose another hall.
                  </p>
                )}
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Applicant Name
                </label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input
                    type="text"
                    name="applicantName"
                    value={formData.applicantName}
                    onChange={handleInputChange}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Contact Number
                </label>
                <div className="relative">
                  <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input
                    type="tel"
                    name="contactNumber"
                    value={formData.contactNumber}
                    onChange={handleInputChange}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    required
                  />
                </div>
              </div>


              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Department/Institution
                </label>
                <div className="relative">
                  <Building className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input
                    type="text"
                    name="department"
                    value={formData.department}
                    onChange={handleInputChange}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Required Hall
                </label>
                <input
                  type="text"
                  name="requiredHall"
                  value={formData.requiredHall}
                  readOnly
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Organizing Department/Institution
                </label>
                <input
                  type="text"
                  name="organizingDepartment"
                  value={formData.organizingDepartment}
                  onChange={handleInputChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Seating Capacity Required
                </label>
                <div className="relative">
                  <Users className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input
                    type="number"
                    name="seatingCapacity"
                    value={formData.seatingCapacity}
                    onChange={handleInputChange}
                    max={hall.capacity}
                    className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent ${
                      formData.seatingCapacity && hall && parseInt(formData.seatingCapacity) > hall.capacity
                        ? 'border-red-300 bg-red-50'
                        : 'border-gray-300'
                    }`}
                    required
                  />
                </div>
                {formData.seatingCapacity && hall && parseInt(formData.seatingCapacity) > hall.capacity && (
                  <p className="mt-2 text-sm text-red-600 flex items-center">
                    <XCircle className="h-4 w-4 mr-1" />
                    Please enter seating capacity within the hall's limit. Maximum capacity is {hall.capacity} seats.
                  </p>
                )}
                {formData.seatingCapacity && hall && parseInt(formData.seatingCapacity) <= hall.capacity && parseInt(formData.seatingCapacity) > 0 && (
                  <p className="mt-2 text-sm text-green-600 flex items-center">
                    <CheckCircle className="h-4 w-4 mr-1" />
                    Capacity is within the hall's limit ({hall.capacity} seats).
                  </p>
                )}
              </div>
            </div>

            {/* Number of Days - Full Width Row */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Number of Days Required
              </label>
              <select
                name="numberOfDays"
                value={formData.numberOfDays}
                onChange={handleNumberOfDaysChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                required
              >
                <option value={1}>1 day</option>
                <option value={2}>2 days</option>
                <option value={3}>3 days</option>
                <option value={4}>4 days</option>
                <option value={5}>5 days</option>
              </select>
            </div>

            {/* Dynamic Date/Time Selection */}
            {formData.numberOfDays === 1 ? (
              /* Single Day Selection */
              <div className="mb-6">
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                  <h3 className="text-lg font-semibold text-blue-800 mb-2 flex items-center">
                    <Calendar className="h-5 w-5 mr-2" />
                    Single Day Booking
                  </h3>
                  <p className="text-blue-600 text-sm">Select your event date and time</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Date Selection */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      📅 Event Date
                    </label>
                    <div className="relative">
                      <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                      <input
                        type="date"
                        name="eventDate"
                        value={formData.eventDate}
                        onChange={handleDateChange}
                        min={getMinDate()}
                        max={getMaxDate()}
                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        required
                      />
                    </div>
                  </div>

                  {/* Time Selection */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      ⏰ Event Time
                    </label>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs text-gray-600 mb-1">From</label>
                        <select
                          name="timeFrom"
                          value={formData.timeFrom}
                          onChange={handleInputChange}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                          required
                        >
                          <option value="">Select time</option>
                          {timeOptions.map(time => (
                            <option key={time} value={time}>{time}</option>
                          ))}
                        </select>
                      </div>
                      <div>
                        <label className="block text-xs text-gray-600 mb-1">To</label>
                        <select
                          name="timeTo"
                          value={formData.timeTo}
                          onChange={handleInputChange}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                          required
                        >
                          <option value="">Select time</option>
                          {timeOptions.map(time => (
                            <option key={time} value={time}>{time}</option>
                          ))}
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              /* Multi-Day Selection */
              <div className="mb-6">
                <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                  <h3 className="text-lg font-semibold text-green-800 mb-2 flex items-center">
                    <Calendar className="h-5 w-5 mr-2" />
                    Multi-Day Booking ({formData.numberOfDays} days)
                  </h3>
                  <p className="text-green-600 text-sm">Select check-in and check-out dates with times</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Check-in Date & Time */}
                  <div className="space-y-4">
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                      <h4 className="font-semibold text-blue-800 mb-3 flex items-center">
                        <Clock className="h-4 w-4 mr-2" />
                        Check-in
                      </h4>
                      
                      <div className="space-y-3">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            📅 Check-in Date
                          </label>
                          <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                              type="date"
                              name="checkInDate"
                              value={formData.checkInDate}
                              onChange={handleDateChange}
                              min={getMinDate()}
                              max={getMaxDate()}
                              className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                              required
                            />
                          </div>
                        </div>
                        
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            ⏰ Check-in Time
                          </label>
                          <select
                            name="checkInTime"
                            value={formData.checkInTime}
                            onChange={handleInputChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                            required
                          >
                            <option value="">Select time</option>
                            {timeOptions.map(time => (
                              <option key={time} value={time}>{time}</option>
                            ))}
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Check-out Date & Time */}
                  <div className="space-y-4">
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                      <h4 className="font-semibold text-red-800 mb-3 flex items-center">
                        <Clock className="h-4 w-4 mr-2" />
                        Check-out
                      </h4>
                      
                      <div className="space-y-3">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            📅 Check-out Date
                          </label>
                          <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                              type="date"
                              name="checkOutDate"
                              value={formData.checkOutDate}
                              onChange={handleInputChange}
                              min={formData.checkInDate || getMinDate()}
                              max={getMaxDate()}
                              className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                              required
                            />
                          </div>
                        </div>
                        
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            ⏰ Check-out Time
                          </label>
                          <select
                            name="checkOutTime"
                            value={formData.checkOutTime}
                            onChange={handleInputChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                            required
                          >
                            <option value="">Select time</option>
                            {timeOptions.map(time => (
                              <option key={time} value={time}>{time}</option>
                            ))}
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Summary */}
                {formData.checkInDate && formData.checkOutDate && (
                  <div className="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 className="font-semibold text-gray-800 mb-2">📋 Booking Summary</h4>
                    <div className="text-sm text-gray-600">
                      <p><strong>Check-in:</strong> {formData.checkInDate} at {formData.checkInTime || '--:--'}</p>
                      <p><strong>Check-out:</strong> {formData.checkOutDate} at {formData.checkOutTime || '--:--'}</p>
                      <p><strong>Duration:</strong> {formData.numberOfDays} day{formData.numberOfDays > 1 ? 's' : ''}</p>
                    </div>
                  </div>
                )}
              </div>
            )}

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Purpose of the Hall
              </label>
              <div className="relative">
                <FileText className="absolute left-3 top-3 h-5 w-5 text-gray-400" />
                <textarea
                  name="purpose"
                  value={formData.purpose}
                  onChange={handleInputChange}
                  rows={4}
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  placeholder="Describe the purpose of booking this hall..."
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Facilities Required
              </label>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {availableFacilities.map((facility: string) => (
                  <label key={facility} className="flex items-center space-x-2">
                    <input
                      type="checkbox"
                      checked={formData.facilitiesRequired.includes(facility)}
                      onChange={() => handleFacilityChange(facility)}
                      className="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded"
                    />
                    <span className="text-gray-700">{facility}</span>
                  </label>
                ))}
              </div>
            </div>

            <div className="flex justify-end space-x-4">
              <button
                type="button"
                onClick={() => navigate('/dashboard/book-hall')}
                className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={submitting || availabilityStatus.available === false}
                className={`px-6 py-3 rounded-lg transition-colors font-medium ${
                  submitting || availabilityStatus.available === false
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    : 'bg-primary text-white hover:bg-primary-dark'
                }`}
              >
                {submitting ? 'Submitting...' : 'Submit Booking Request'}
              </button>
            </div>
          </form>
        </div>
      </div>

      {/* Custom Success Modal */}
      {showSuccessModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-2xl p-8 max-w-md mx-4 shadow-2xl transform transition-all duration-300 scale-100">
            <div className="text-center">
              {/* Success Icon */}
              <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <CheckCircle className="h-10 w-10 text-green-600" />
              </div>
              
              {/* Success Message */}
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                Booking Submitted Successfully!
              </h3>
              
              <p className="text-gray-600 mb-6 leading-relaxed">
                Thank you for your booking request. Please wait for admin approval. 
                You will be notified once your booking is reviewed.
              </p>
              
              {/* Action Buttons */}
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button
                  onClick={() => {
                    setShowSuccessModal(false);
                    navigate('/dashboard');
                  }}
                  className="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl"
                >
                  Go to Dashboard
                </button>
                <button
                  onClick={() => setShowSuccessModal(false)}
                  className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors"
                >
                  Stay Here
                </button>
              </div>
              
              {/* Auto-redirect notice */}
              <p className="text-sm text-gray-500 mt-4">
                Redirecting to dashboard in 3 seconds...
              </p>
            </div>
          </div>
        </div>
      )}
    </DashboardLayout>
  );
}