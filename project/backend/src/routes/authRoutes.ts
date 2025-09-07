import { Router, Request, Response } from 'express';
import { authenticateToken, AuthRequest } from '../middleware/authMiddleware';
import { UserService } from '../services/userService';
import { ApiResponse, User } from '../types';

const router = Router();

/**
 * POST /api/auth/register
 * Register a new user (called after Firebase Auth signup)
 */
router.post('/register', async (req: Request, res: Response): Promise<void> => {
  try {
    const { uid, name, mobile, email, designation, department } = req.body;

    // Validate required fields
    if (!uid || !name || !mobile || !email || !designation) {
      res.status(400).json({
        success: false,
        message: 'Missing required fields',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    // Check if user already exists
    const existingUser = await UserService.getUserById(uid);
    if (existingUser) {
      res.status(409).json({
        success: false,
        message: 'User already exists',
        error: 'USER_EXISTS'
      } as ApiResponse);
      return;
    }

    // Create user in Firestore
    const newUser = await UserService.createUser({
      uid,
      name,
      mobile,
      email,
      designation,
      department: department || '',
      role: 'user', // Default role
    });

    res.status(201).json({
      success: true,
      message: 'User registered successfully',
      data: {
        user: {
          uid: newUser.uid,
          name: newUser.name,
          email: newUser.email,
          mobile: newUser.mobile,
          designation: newUser.designation,
          department: newUser.department,
          role: newUser.role,
        }
      }
    } as ApiResponse<{ user: Partial<User> }>);

  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to register user',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * GET /api/auth/me
 * Get current user profile
 */
router.get('/me', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    res.json({
      success: true,
      message: 'User profile retrieved successfully',
      data: {
        user: {
          uid: req.user.uid,
          name: req.user.name,
          email: req.user.email,
          mobile: req.user.mobile,
          designation: req.user.designation,
          department: req.user.department,
          cabinNumber: req.user.cabinNumber,
          officeLocation: req.user.officeLocation,
          role: req.user.role,
          createdAt: req.user.createdAt,
          lastLogin: req.user.lastLogin,
          updatedAt: req.user.updatedAt,
        }
      }
    } as ApiResponse<{ user: User }>);

  } catch (error) {
    console.error('Get profile error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to get user profile',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * PUT /api/auth/profile
 * Update user profile
 */
router.put('/profile', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'User not authenticated',
        error: 'UNAUTHORIZED'
      } as ApiResponse);
      return;
    }

    const { name, mobile, designation, department, cabinNumber, officeLocation } = req.body;

    // Validate at least one field is provided
    if (!name && !mobile && !designation && !department && !cabinNumber && !officeLocation) {
      res.status(400).json({
        success: false,
        message: 'At least one field must be provided for update',
        error: 'VALIDATION_ERROR'
      } as ApiResponse);
      return;
    }

    // Prepare update data - only include fields that have values
    const updateData: any = {
      updatedAt: new Date()
    };
    if (name && name.trim()) updateData.name = name.trim();
    if (mobile && mobile.trim()) updateData.mobile = mobile.trim();
    if (designation && designation.trim()) updateData.designation = designation.trim();
    if (department && department.trim()) updateData.department = department.trim();
    if (cabinNumber && cabinNumber.trim()) updateData.cabinNumber = cabinNumber.trim();
    if (officeLocation && officeLocation.trim()) updateData.officeLocation = officeLocation.trim();

    // Update user
    const updatedUser = await UserService.updateUser(req.user.uid, updateData);

    res.json({
      success: true,
      message: 'Profile updated successfully',
      data: {
        user: {
          uid: updatedUser.uid,
          name: updatedUser.name,
          email: updatedUser.email,
          mobile: updatedUser.mobile,
          designation: updatedUser.designation,
          department: updatedUser.department,
          cabinNumber: updatedUser.cabinNumber,
          officeLocation: updatedUser.officeLocation,
          role: updatedUser.role,
        }
      }
    } as ApiResponse<{ user: Partial<User> }>);

  } catch (error) {
    console.error('Update profile error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update profile',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

/**
 * POST /api/auth/logout
 * Logout user (optional endpoint for cleanup)
 */
router.post('/logout', authenticateToken, async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    // In a real app, you might want to invalidate refresh tokens or perform cleanup
    res.json({
      success: true,
      message: 'Logged out successfully'
    } as ApiResponse);

  } catch (error) {
    console.error('Logout error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to logout',
      error: 'INTERNAL_SERVER_ERROR'
    } as ApiResponse);
  }
});

export default router;