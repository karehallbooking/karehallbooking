import { Router, Response } from 'express';
import { authenticateToken, requireAdmin, AuthRequest } from '../middleware/authMiddleware';
import { BookingService } from '../services/bookingService';
import { UserService } from '../services/userService';
import { db, collections } from '../config/firebase';
import { ApiResponse, Booking, Hall } from '../types';
import { sendMail } from '../utils/mailer';
import { bookingApproved, bookingRejected } from '../utils/emailTemplates';

const router = Router();

// Apply authentication and admin role check to all routes
router.use(authenticateToken);
router.use(requireAdmin);

/**
 * GET /api/admin/bookings/pending
 * Get all pending bookings
 */
router.get('/bookings/pending', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const page = parseInt(req.query.page as string) || 1;
    const limit = parseInt(req.query.limit as string) || 20;
    const sortBy = req.query.sortBy as string || 'createdAt';
    const sortOrder = req.query.sortOrder as 'asc' | 'desc' || 'desc';

    const result = await BookingService.getPendingBookings({
      page,
      limit,
      sortBy,
      sortOrder
    });

    res.json({
      success: true,
      message: 'Pending bookings retrieved successfully',
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
    console.error('Get pending bookings error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve pending bookings',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/admin/bookings/approved
 * Get all approved bookings
 */
router.get('/bookings/approved', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const page = parseInt(req.query.page as string) || 1;
    const limit = parseInt(req.query.limit as string) || 20;
    const sortBy = req.query.sortBy as string || 'createdAt';
    const sortOrder = req.query.sortOrder as 'asc' | 'desc' || 'desc';

    const result = await BookingService.getApprovedBookings({
      page,
      limit,
      sortBy,
      sortOrder
    });

    res.json({
      success: true,
      message: 'Approved bookings retrieved successfully',
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
    console.error('Get approved bookings error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve approved bookings',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/admin/bookings/rejected
 * Get all rejected bookings
 */
router.get('/bookings/rejected', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const page = parseInt(req.query.page as string) || 1;
    const limit = parseInt(req.query.limit as string) || 20;
    const sortBy = req.query.sortBy as string || 'createdAt';
    const sortOrder = req.query.sortOrder as 'asc' | 'desc' || 'desc';

    const result = await BookingService.getRejectedBookings({
      page,
      limit,
      sortBy,
      sortOrder
    });

    res.json({
      success: true,
      message: 'Rejected bookings retrieved successfully',
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
    console.error('Get rejected bookings error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve rejected bookings',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/admin/bookings/history
 * Get all bookings with filters
 */
router.get('/bookings/history', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const page = parseInt(req.query.page as string) || 1;
    const limit = parseInt(req.query.limit as string) || 20;
    const sortBy = req.query.sortBy as string || 'createdAt';
    const sortOrder = req.query.sortOrder as 'asc' | 'desc' || 'desc';

    // Extract filters
    const filters = {
      status: req.query.status as 'pending' | 'approved' | 'rejected',
      hallName: req.query.hallName as string,
      department: req.query.department as string,
      userId: req.query.userId as string,
    };

    // Remove undefined filters
    Object.keys(filters).forEach(key => {
      if (filters[key as keyof typeof filters] === undefined) {
        delete filters[key as keyof typeof filters];
      }
    });

    const result = await BookingService.getAllBookings(filters, {
      page,
      limit,
      sortBy,
      sortOrder
    });

    res.json({
      success: true,
      message: 'Booking history retrieved successfully',
      data: {
        bookings: result.bookings,
        pagination: {
          page,
          limit,
          total: result.total,
          totalPages: Math.ceil(result.total / limit)
        },
        filters
      }
    } as ApiResponse);

  } catch (error) {
    console.error('Get booking history error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve booking history',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PATCH /api/admin/bookings/:id/approve
 * Approve a booking
 */
router.patch('/bookings/:id/approve', async (req: AuthRequest, res: Response): Promise<void> => {
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
    const { adminComments } = req.body;

    const booking = await BookingService.getBookingById(bookingId);

    if (!booking) {
      res.status(404).json({
        success: false,
        message: 'Booking not found',
        error: 'BOOKING_NOT_FOUND'
      } as ApiResponse);
      return;
    }

    if (booking.status !== 'pending') {
      res.status(400).json({
        success: false,
        message: 'Only pending bookings can be approved',
        error: 'BOOKING_NOT_PENDING'
      } as ApiResponse);
      return;
    }

    // Check for conflicts before approving
    const conflicts = await BookingService.checkBookingConflicts(
      booking.hallName,
      booking.dates,
      booking.timeFrom,
      booking.timeTo,
      bookingId
    );

    if (conflicts.length > 0) {
      res.status(409).json({
        success: false,
        message: 'Cannot approve booking due to conflicts with other approved bookings',
        error: 'BOOKING_CONFLICT',
        data: { conflicts }
      } as ApiResponse);
      return;
    }

    const updatedBooking = await BookingService.updateBookingStatus(
      bookingId,
      'approved',
      req.user.uid,
      adminComments
    );

    // Email user about approval
    try {
      const hallName = updatedBooking.hallName || (updatedBooking as any).hall || 'Hall';
      const emailData = {
        bookingId,
        hallName,
        dates: updatedBooking.dates || [],
        timeFrom: updatedBooking.timeFrom,
        timeTo: updatedBooking.timeTo,
        purpose: updatedBooking.purpose,
        peopleCount: updatedBooking.seatingCapacity,
        bookedBy: updatedBooking.userName,
        contact: updatedBooking.userMobile
      };
      if (updatedBooking.userEmail) {
        await sendMail({
          to: updatedBooking.userEmail,
          subject: `Your booking is approved ✅ — ${hallName}`,
          html: bookingApproved(emailData),
          text: `Your booking is approved. View: ${(process.env.PUBLIC_SITE_URL || '')}/my-bookings/${bookingId}`
        });
      }
    } catch (e: any) {
      console.error('❌ Booking approval mail failed:', e?.message || e);
    }

    res.json({
      success: true,
      message: 'Booking approved successfully',
      data: { booking: updatedBooking }
    } as ApiResponse<{ booking: Booking }>);

  } catch (error) {
    console.error('Approve booking error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to approve booking',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PATCH /api/admin/bookings/:id/reject
 * Reject a booking
 */
router.patch('/bookings/:id/reject', async (req: AuthRequest, res: Response): Promise<void> => {
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
    const { adminComments } = req.body;

    const booking = await BookingService.getBookingById(bookingId);

    if (!booking) {
      res.status(404).json({
        success: false,
        message: 'Booking not found',
        error: 'BOOKING_NOT_FOUND'
      } as ApiResponse);
      return;
    }

    if (booking.status !== 'pending') {
      res.status(400).json({
        success: false,
        message: 'Only pending bookings can be rejected',
        error: 'BOOKING_NOT_PENDING'
      } as ApiResponse);
      return;
    }

    const updatedBooking = await BookingService.updateBookingStatus(
      bookingId,
      'rejected',
      req.user.uid,
      adminComments
    );

    // Email user about rejection
    try {
      const hallName = updatedBooking.hallName || (updatedBooking as any).hall || 'Hall';
      const emailData = {
        bookingId,
        hallName,
        dates: updatedBooking.dates || [],
        timeFrom: updatedBooking.timeFrom,
        timeTo: updatedBooking.timeTo,
        purpose: updatedBooking.purpose,
        rejectionReason: adminComments
      };
      if (updatedBooking.userEmail) {
        await sendMail({
          to: updatedBooking.userEmail,
          subject: `Update on your booking request — ${hallName}`,
          html: bookingRejected(emailData),
          text: `Your booking was not approved.${adminComments ? ' Reason: ' + adminComments : ''} Try again: ${(process.env.PUBLIC_SITE_URL || '')}/book`
        });
      }
    } catch (e: any) {
      console.error('❌ Booking rejection mail failed:', e?.message || e);
    }

    res.json({
      success: true,
      message: 'Booking rejected successfully',
      data: { booking: updatedBooking }
    } as ApiResponse<{ booking: Booking }>);

  } catch (error) {
    console.error('Reject booking error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to reject booking',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/admin/halls
 * Get all halls
 */
router.get('/halls', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const snapshot = await db.collection(collections.halls).orderBy('hallName').get();
    
    const halls = snapshot.docs.map(doc => ({
      hallId: doc.id,
      ...doc.data()
    })) as Hall[];

    res.json({
      success: true,
      message: 'Halls retrieved successfully',
      data: { halls }
    } as ApiResponse<{ halls: Hall[] }>);

  } catch (error) {
    console.error('Get halls error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve halls',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * POST /api/admin/halls
 * Add a new hall
 */
router.post('/halls', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const { hallName, capacity, facilities } = req.body;

    if (!hallName || !capacity) {
      res.status(400).json({
        success: false,
        message: 'Hall name and capacity are required',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    if (!Array.isArray(facilities)) {
      res.status(400).json({
        success: false,
        message: 'Facilities must be an array',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    const hallRef = db.collection(collections.halls).doc();
    
    const newHall: Hall = {
      hallId: hallRef.id,
      hallName,
      capacity: parseInt(capacity),
      facilities,
      active: true,
      createdAt: new Date() as any,
      updatedAt: new Date() as any,
    };

    await hallRef.set(newHall);

    res.status(201).json({
      success: true,
      message: 'Hall created successfully',
      data: { hall: newHall }
    } as ApiResponse<{ hall: Hall }>);

  } catch (error) {
    console.error('Create hall error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create hall',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PUT /api/admin/halls/:id
 * Update a hall
 */
router.put('/halls/:id', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const hallId = req.params.id;
    const { hallName, capacity, facilities, active } = req.body;

    const hallRef = db.collection(collections.halls).doc(hallId);
    const hallDoc = await hallRef.get();

    if (!hallDoc.exists) {
      res.status(404).json({
        success: false,
        message: 'Hall not found',
        error: 'HALL_NOT_FOUND'
      } as ApiResponse);
      return;
    }

    const updateData: any = {
      updatedAt: new Date(),
    };

    if (hallName) updateData.hallName = hallName;
    if (capacity) updateData.capacity = parseInt(capacity);
    if (facilities && Array.isArray(facilities)) updateData.facilities = facilities;
    if (active !== undefined) updateData.active = active;

    await hallRef.update(updateData);

    const updatedDoc = await hallRef.get();
    const updatedHall = { hallId, ...updatedDoc.data() } as Hall;

    res.json({
      success: true,
      message: 'Hall updated successfully',
      data: { hall: updatedHall }
    } as ApiResponse<{ hall: Hall }>);

  } catch (error) {
    console.error('Update hall error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update hall',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * DELETE /api/admin/halls/:id
 * Delete a hall
 */
router.delete('/halls/:id', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const hallId = req.params.id;

    // Check if hall has any bookings
    const bookingsSnapshot = await db
      .collection(collections.bookings)
      .where('hallName', '==', hallId)
      .limit(1)
      .get();

    if (!bookingsSnapshot.empty) {
      res.status(400).json({
        success: false,
        message: 'Cannot delete hall with existing bookings',
        error: 'HALL_HAS_BOOKINGS'
      } as ApiResponse);
      return;
    }

    await db.collection(collections.halls).doc(hallId).delete();

    res.json({
      success: true,
      message: 'Hall deleted successfully'
    } as ApiResponse);

  } catch (error) {
    console.error('Delete hall error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to delete hall',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/admin/stats
 * Get booking statistics
 */
router.get('/stats', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const stats = await BookingService.getBookingStats();

    res.json({
      success: true,
      message: 'Statistics retrieved successfully',
      data: { stats }
    } as ApiResponse);

  } catch (error) {
    console.error('Get stats error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve statistics',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/admin/users
 * Get all users
 */
router.get('/users', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const page = parseInt(req.query.page as string) || 1;
    const limit = parseInt(req.query.limit as string) || 20;
    const offset = (page - 1) * limit;

    const users = await UserService.getAllUsers(limit, offset);

    res.json({
      success: true,
      message: 'Users retrieved successfully',
      data: {
        users,
        pagination: {
          page,
          limit,
          // Note: We don't have total count here, would need to implement separately
        }
      }
    } as ApiResponse);

  } catch (error) {
    console.error('Get users error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to retrieve users',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PATCH /api/admin/users/:id/role
 * Update user role
 */
router.patch('/users/:id/role', async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const userId = req.params.id;
    const { role } = req.body;

    if (!role || !['user', 'admin'].includes(role)) {
      res.status(400).json({
        success: false,
        message: 'Valid role (user or admin) is required',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    const updatedUser = await UserService.updateUserRole(userId, role);

    res.json({
      success: true,
      message: 'User role updated successfully',
      data: { user: updatedUser }
    } as ApiResponse);

  } catch (error) {
    console.error('Update user role error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update user role',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

export default router;