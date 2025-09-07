import { Request, Response, NextFunction } from 'express';
import { auth, db, collections } from '../config/firebase';
import { User, AuthenticatedRequest } from '../types';

export interface AuthRequest extends Request {
  user?: User;
}

/**
 * Middleware to verify Firebase ID token and attach user to request
 */
export const authenticateToken = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
): Promise<void> => {
  try {
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      res.status(401).json({
        success: false,
        message: 'Access token is missing or invalid',
        error: 'UNAUTHORIZED'
      });
      return;
    }

    const token = authHeader.substring(7); // Remove 'Bearer ' prefix

    // Verify the Firebase ID token
    const decodedToken = await auth.verifyIdToken(token);
    const uid = decodedToken.uid;

    // Fetch user data from Firestore
    const userDoc = await db.collection(collections.users).doc(uid).get();
    
    if (!userDoc.exists) {
      res.status(404).json({
        success: false,
        message: 'User not found in database',
        error: 'USER_NOT_FOUND'
      });
      return;
    }

    const userData = userDoc.data() as User;
    
    // Update last login timestamp
    await db.collection(collections.users).doc(uid).update({
      lastLogin: new Date()
    });

    // Attach user to request object
    req.user = {
      ...userData,
      uid
    };

    next();
  } catch (error: any) {
    console.error('Authentication error:', error);
    
    if (error.code === 'auth/id-token-expired') {
      res.status(401).json({
        success: false,
        message: 'Token has expired',
        error: 'TOKEN_EXPIRED'
      });
      return;
    }

    if (error.code === 'auth/id-token-revoked') {
      res.status(401).json({
        success: false,
        message: 'Token has been revoked',
        error: 'TOKEN_REVOKED'
      });
      return;
    }

    res.status(401).json({
      success: false,
      message: 'Invalid or expired token',
      error: 'INVALID_TOKEN'
    });
  }
};

/**
 * Middleware to check if user has required role
 */
export const requireRole = (requiredRole: 'user' | 'admin') => {
  return (req: AuthRequest, res: Response, next: NextFunction): void => {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'Authentication required',
        error: 'UNAUTHORIZED'
      });
      return;
    }

    if (req.user.role !== requiredRole && requiredRole === 'admin') {
      res.status(403).json({
        success: false,
        message: 'Admin access required',
        error: 'FORBIDDEN'
      });
      return;
    }

    next();
  };
};

/**
 * Middleware to check if user is admin
 */
export const requireAdmin = requireRole('admin');

/**
 * Optional authentication - doesn't fail if no token provided
 */
export const optionalAuth = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
): Promise<void> => {
  try {
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      next();
      return;
    }

    const token = authHeader.substring(7);
    const decodedToken = await auth.verifyIdToken(token);
    const uid = decodedToken.uid;

    const userDoc = await db.collection(collections.users).doc(uid).get();
    
    if (userDoc.exists) {
      const userData = userDoc.data() as User;
      req.user = { ...userData, uid };
    }

    next();
  } catch (error) {
    // Continue without authentication for optional auth
    next();
  }
};