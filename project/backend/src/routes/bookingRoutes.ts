import { Router, Response, Request } from 'express';
import { authenticateToken, AuthRequest } from '../middleware/authMiddleware';
import { BookingService } from '../services/bookingService';
import { ApiResponse, Booking } from '../types';
import { sendMail } from '../utils/mailer';
import { bookingReceivedUser, bookingReceivedAdmin } from '../utils/emailTemplates';
import { db, collections } from '../config/firebase';

const router = Router();

/**
 * POST /api/bookings
 * Create a new booking
 */
router.post('/', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    const {
      hallName,
      department,
      organizingDepartment,
      purpose,
      seatingCapacity,
      facilities,
      dates,
      timeFrom,
      timeTo,
      // Enhanced fields for multi-day bookings
      checkInDate,
      checkInTime,
      checkOutDate,
      checkOutTime,
      numberOfDays
    } = req.body;

    // Validate required fields
    if (!hallName || !department || !organizingDepartment || !purpose || 
        !seatingCapacity || !dates || !timeFrom || !timeTo) {
      res.status(400).json({
        success: false,
        message: 'Missing required fields',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    // Validate dates array
    if (!Array.isArray(dates) || dates.length === 0) {
      res.status(400).json({
        success: false,
        message: 'At least one date must be provided',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    // Validate facilities array
    if (facilities && !Array.isArray(facilities)) {
      res.status(400).json({
        success: false,
        message: 'Facilities must be an array',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    // Validate dates and 1-month restriction
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + 30);
    maxDate.setHours(23, 59, 59, 999);

    for (const dateStr of dates) {
      const selectedDate = new Date(dateStr);
      
      if (isNaN(selectedDate.getTime())) {
        res.status(400).json({
          success: false,
          message: `Invalid date format: ${dateStr}. Use YYYY-MM-DD`,
          error: 'INVALID_DATE_FORMAT'
        } as ApiResponse);
        return;
      }

      if (selectedDate < today) {
        res.status(400).json({
          success: false,
          message: 'Cannot book for past dates',
          error: 'PAST_DATE_NOT_ALLOWED'
        } as ApiResponse);
        return;
      }

      if (selectedDate > maxDate) {
        res.status(400).json({
          success: false,
          message: 'Bookings allowed only within 1 month from today',
          error: 'BOOKING_TOO_FAR_AHEAD'
        } as ApiResponse);
        return;
      }
    }

    // Check for booking conflicts
    const conflicts = await BookingService.checkBookingConflicts(
      hallName,
      dates,
      timeFrom,
      timeTo
    );

    if (conflicts.length > 0) {
      res.status(409).json({
        success: false,
        message: 'Booking conflicts detected for the selected dates and times',
        error: 'BOOKING_CONFLICT',
        data: { conflicts }
      } as ApiResponse);
      return;
    }

    // Create booking
    const bookingData = {
      userId: req.user.uid,
      userName: req.user.name,
      userEmail: req.user.email,
      userMobile: req.user.mobile,
      userDesignation: req.user.designation || 'Faculty',
      userDepartment: req.user.department,
      hallName,
      department,
      organizingDepartment,
      purpose,
      seatingCapacity: parseInt(seatingCapacity),
      facilities: facilities || [],
      dates,
      timeFrom,
      timeTo,
      // Enhanced fields for multi-day bookings
      checkInDate,
      checkInTime,
      checkOutDate,
      checkOutTime,
      numberOfDays: numberOfDays ? parseInt(numberOfDays) : 1,
      status: 'pending' as const,
    };

    const newBooking = await BookingService.createBooking(bookingData);

    // Logging + transactional emails (user + admin)
    try {
      console.log('📌 Booking request received');
      console.log(`✅ Booking saved with ID: ${newBooking.bookingId}`);

      const hallNameDisplay = newBooking.hallName || (newBooking as any).hall || 'Hall';
      const emailData = {
        bookingId: newBooking.bookingId as string,
        hallName: hallNameDisplay,
        dates: newBooking.dates || [],
        timeFrom: newBooking.timeFrom,
        timeTo: newBooking.timeTo,
        purpose: newBooking.purpose,
        peopleCount: newBooking.seatingCapacity,
        bookedBy: newBooking.userName,
        contact: newBooking.userMobile,
        userEmail: newBooking.userEmail
      };

      if (newBooking.userEmail) {
        try {
          await sendMail({
            to: newBooking.userEmail,
            subject: `We received your booking request — ${hallNameDisplay}`,
            html: bookingReceivedUser(emailData),
            text: `We received your booking request for ${hallNameDisplay}. View: https://karehallbooking.netlify.app/my-bookings/${newBooking.bookingId}`
          });
        } catch (e: any) {
          console.error('❌ User booking mail failed:', e?.message || e);
        }
      }

      if (process.env.ADMIN_EMAIL) {
        try {
          await sendMail({
            to: process.env.ADMIN_EMAIL!,
            subject: `New booking request — ${hallNameDisplay}`,
            html: bookingReceivedAdmin(emailData),
            text: `New booking ID: ${newBooking.bookingId}`
          });
        } catch (e: any) {
          console.error('❌ Admin booking mail failed:', e?.message || e);
        }
      }
    } catch (logErr) {
      console.error('❌ Post-create mail/log error:', (logErr as any)?.message || logErr);
    }

    res.status(201).json({
      success: true,
      message: 'Booking created successfully',
      data: { booking: newBooking }
    } as ApiResponse<{ booking: Booking }>);

  } catch (error) {
    console.error('Create booking error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create booking',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/bookings/my
 * Get current user's bookings
 */
router.get('/my', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    const page = parseInt(req.query.page as string) || 1;
    const limit = parseInt(req.query.limit as string) || 10;
    const sortBy = req.query.sortBy as string || 'createdAt';
    const sortOrder = req.query.sortOrder as 'asc' | 'desc' || 'desc';

    const result = await BookingService.getUserBookings(req.user.uid, {
      page,
      limit,
      sortBy,
      sortOrder
    });

    res.json({
      success: true,
      message: 'Bookings retrieved successfully',
      data: {
        bookings: result.bookings,
        pagination: {
          page,
          limit,
          total: result.total,
          totalPages: Math.ceil(result.total / limit)
        }
      }
    } as ApiResponse);

  } catch (error) {
    console.error('Get user bookings error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve bookings',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/bookings/:id
 * Get a specific booking by ID
 */
router.get('/:id', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    const bookingId = req.params.id;
    const booking = await BookingService.getBookingById(bookingId);

    if (!booking) {
      res.status(404).json({
        success: false,
        message: 'Booking not found',
        error: 'BOOKING_NOT_FOUND'
      } as ApiResponse);
      return;
    }

    // Check if user owns this booking or is admin
    if (booking.userId !== req.user.uid && req.user.role !== 'admin') {
      res.status(403).json({
        success: false,
        message: 'Access denied',
        error: 'FORBIDDEN'
      } as ApiResponse);
      return;
    }

    res.json({
      success: true,
      message: 'Booking retrieved successfully',
      data: { booking }
    } as ApiResponse<{ booking: Booking }>);

  } catch (error) {
    console.error('Get booking error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve booking',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PUT /api/bookings/:id
 * Update a booking (only if pending and user owns it)
 */
router.put('/:id', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    const bookingId = req.params.id;
    const booking = await BookingService.getBookingById(bookingId);

    if (!booking) {
      res.status(404).json({
        success: false,
        message: 'Booking not found',
        error: 'BOOKING_NOT_FOUND'
      } as ApiResponse);
      return;
    }

    // Check if user owns this booking
    if (booking.userId !== req.user.uid) {
      res.status(403).json({
        success: false,
        message: 'Access denied',
        error: 'FORBIDDEN'
      } as ApiResponse);
      return;
    }

    // Check if booking is still pending
    if (booking.status !== 'pending') {
      res.status(400).json({
        success: false,
        message: 'Only pending bookings can be updated',
        error: 'BOOKING_NOT_EDITABLE'
      } as ApiResponse);
      return;
    }

    // For now, we'll return a message that updates are not implemented
    // In a full implementation, you would update the booking here
    res.status(501).json({
      success: false,
      message: 'Booking updates not implemented yet',
      error: 'NOT_IMPLEMENTED'
    } as ApiResponse);

  } catch (error) {
    console.error('Update booking error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update booking',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * DELETE /api/bookings/:id
 * Cancel a booking (only if pending and user owns it)
 */
router.delete('/:id', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    const bookingId = req.params.id;
    const booking = await BookingService.getBookingById(bookingId);

    if (!booking) {
      res.status(404).json({
        success: false,
        message: 'Booking not found',
        error: 'BOOKING_NOT_FOUND'
      } as ApiResponse);
      return;
    }

    // Check if user owns this booking
    if (booking.userId !== req.user.uid) {
      res.status(403).json({
        success: false,
        message: 'Access denied',
        error: 'FORBIDDEN'
      } as ApiResponse);
      return;
    }

    // Check if booking can be cancelled
    if (booking.status === 'approved') {
      res.status(400).json({
        success: false,
        message: 'Approved bookings cannot be cancelled. Please contact admin.',
        error: 'BOOKING_NOT_CANCELLABLE'
      } as ApiResponse);
      return;
    }

    await BookingService.deleteBooking(bookingId);

    res.json({
      success: true,
      message: 'Booking cancelled successfully'
    } as ApiResponse);

  } catch (error) {
    console.error('Cancel booking error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to cancel booking',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PATCH /api/bookings/:id/request-cancel
 * User submits a cancellation review for admin approval
 */
router.patch('/:id/request-cancel', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({ success: false, message: 'User not authenticated', error: 'UNAUTHORIZED' } as ApiResponse);
      return;
    }
    const bookingId = req.params.id;
    const { reason } = req.body as { reason: string };
    if (!reason || !reason.trim()) {
      res.status(400).json({ success: false, message: 'Reason is required', error: 'VALIDATION_ERROR' } as ApiResponse);
      return;
    }

    const docRef = db.collection(collections.bookings).doc(bookingId);
    const snap = await docRef.get();
    if (!snap.exists) {
      res.status(404).json({ success: false, message: 'Booking not found', error: 'BOOKING_NOT_FOUND' } as ApiResponse);
      return;
    }
    const booking = snap.data() as Booking & { userId: string };
    if (booking.userId !== req.user.uid) {
      res.status(403).json({ success: false, message: 'Access denied', error: 'FORBIDDEN' } as ApiResponse);
      return;
    }

    await docRef.update({
      reviewStatus: 'pending',
      reviewType: 'cancel',
      reviewReason: reason.trim(),
      reviewRequestedBy: req.user.uid,
      updatedAt: new Date()
    });

    res.json({ success: true, message: 'Cancellation request submitted' } as ApiResponse);
  } catch (error) {
    console.error('request-cancel error:', error);
    res.status(500).json({ success: false, message: 'Failed to submit cancellation', error: 'INTERNAL_SERVER_ERROR' } as ApiResponse);
  }
});

/**
 * GET /api/bookings/check-availability
 * Check if a hall is available on a specific date
 */
router.get('/check-availability', async (req: Request, res: Response): Promise<void> => {
  try {
    const { hall, date } = req.query;

    if (!hall || !date) {
      res.status(400).json({
        success: false,
        message: 'Hall name and date are required',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    // Validate date format
    const selectedDate = new Date(date as string);
    if (isNaN(selectedDate.getTime())) {
      res.status(400).json({
        success: false,
        message: 'Invalid date format. Use YYYY-MM-DD',
        error: 'INVALID_DATE_FORMAT'
      } as ApiResponse);
      return;
    }

    // Check if date is within 1 month from today
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + 30);
    maxDate.setHours(23, 59, 59, 999);

    if (selectedDate < today) {
      res.json({
        success: true,
        message: 'Date availability checked',
        data: {
          available: false,
          reason: 'Cannot book for past dates'
        }
      } as ApiResponse);
      return;
    }

    if (selectedDate > maxDate) {
      res.json({
        success: true,
        message: 'Date availability checked',
        data: {
          available: false,
          reason: 'Bookings allowed only within 1 month from today'
        }
      } as ApiResponse);
      return;
    }

    // Check for existing bookings on this date
    const dateString = selectedDate.toISOString().split('T')[0];
    const snapshot = await db
      .collection(collections.bookings)
      .where('hallName', '==', hall as string)
      .where('dates', 'array-contains', dateString)
      .where('status', 'in', ['pending', 'approved'])
      .get();

    const available = snapshot.empty;

    res.json({
      success: true,
      message: 'Date availability checked',
      data: {
        available,
        reason: available ? null : 'Hall is already booked on this date'
      }
    } as ApiResponse);

  } catch (error) {
    console.error('Check availability error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to check availability',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/bookings/conflicts/check
 * Check for booking conflicts
 */
router.get('/conflicts/check', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const { hallName, dates, timeFrom, timeTo, excludeBookingId } = req.query;

    if (!hallName || !dates || !timeFrom || !timeTo) {
      res.status(400).json({
        success: false,
        message: 'Missing required parameters',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    const dateArray = Array.isArray(dates) ? dates as string[] : [dates as string];
    
    const conflicts = await BookingService.checkBookingConflicts(
      hallName as string,
      dateArray,
      timeFrom as string,
      timeTo as string,
      excludeBookingId as string
    );

    res.json({
      success: true,
      message: 'Conflict check completed',
      data: {
        hasConflicts: conflicts.length > 0,
        conflicts
      }
    } as ApiResponse);

  } catch (error) {
    console.error('Check conflicts error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to check conflicts',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

export default router;