# KARE Hall Management Backend API

A comprehensive Node.js Express backend for the KARE College Event Hall Management System with Firebase Admin SDK integration.

## 🚀 Features

- **Authentication & Authorization**: Firebase Admin SDK integration with role-based access control
- **Booking Management**: Complete CRUD operations for hall bookings
- **Admin Panel**: Administrative functions for managing bookings and halls
- **Conflict Detection**: Automatic booking conflict checking
- **Security**: Rate limiting, CORS, helmet security headers
- **TypeScript**: Full TypeScript support with proper type definitions
- **Error Handling**: Comprehensive error handling and logging

## 📋 Prerequisites

- Node.js (v16 or higher)
- npm or yarn
- Firebase project with Firestore enabled
- Firebase service account key

## 🛠️ Installation

1. **Clone and navigate to the project:**
   ```bash
   cd hungrysaver-server
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Environment Setup:**
   Create a `.env` file in the backend directory with the following variables:
   ```env
   # Firebase Configuration
   FIREBASE_PROJECT_ID=your-firebase-project-id
   FIREBASE_CLIENT_EMAIL=your-service-account-email@your-project.iam.gserviceaccount.com
   FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY_HERE\n-----END PRIVATE KEY-----\n"
   
   # Server Configuration
   PORT=5000
   NODE_ENV=development
   FRONTEND_URL=http://localhost:5173
   
   # Rate Limiting
   RATE_LIMIT_WINDOW_MS=900000
   RATE_LIMIT_MAX_REQUESTS=100
   ```

4. **Firebase Service Account Setup:**
   - Go to [Firebase Console](https://console.firebase.google.com/)
   - Select your project → Project Settings → Service accounts
   - Click "Generate new private key" and download the JSON file
   - Extract the `project_id`, `client_email`, and `private_key` values
   - Add them to your `.env` file as shown above
   
   **Note:** The `FIREBASE_PRIVATE_KEY` should include the full private key with `-----BEGIN PRIVATE KEY-----` and `-----END PRIVATE KEY-----`

5. **Admin User Setup:**
   - **Default Admin Credentials:**
     - Email: `karehallbooking@gmail.com`
     - Password: `Karehallbooking@198`
   - Create the admin user by visiting `/setup-admin` in the frontend
   - Or use the Firebase Console to create the user manually

## 🏗️ Project Structure

```
hungrysaver-server/
├── src/
│   ├── config/
│   │   └── firebase.ts          # Firebase Admin SDK configuration
│   ├── middleware/
│   │   └── authMiddleware.ts    # Authentication & authorization middleware
│   ├── routes/
│   │   ├── authRoutes.ts        # Authentication endpoints
│   │   ├── bookingRoutes.ts     # Booking management endpoints
│   │   └── adminRoutes.ts       # Admin-only endpoints
│   ├── services/
│   │   ├── userService.ts       # User management service
│   │   └── bookingService.ts    # Booking management service
│   ├── types/
│   │   └── index.ts             # TypeScript type definitions
│   └── index.ts                 # Main application entry point
├── package.json
├── tsconfig.json
├── .env.example
└── README.md
```

## 🗄️ Database Schema

### Users Collection (`users`)
```typescript
{
  uid: string;
  name: string;
  mobile: string;
  email: string;
  designation: 'Student' | 'Faculty' | 'Staff' | 'Guest' | 'Other';
  department?: string;
  role: 'user' | 'admin';
  createdAt: Timestamp;
  lastLogin?: Timestamp;
}
```

### Bookings Collection (`bookings`)
```typescript
{
  bookingId: string;
  userId: string;
  userName: string;
  userEmail: string;
  userMobile: string;
  userDesignation: string;
  userDepartment?: string;
  hallName: string;
  department: string;
  organizingDepartment: string;
  purpose: string;
  seatingCapacity: number;
  facilities: string[];
  dates: string[];
  timeFrom: string;
  timeTo: string;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: Timestamp;
  updatedAt: Timestamp;
  approvedBy?: string;
  rejectedBy?: string;
  adminComments?: string;
}
```

### Halls Collection (`halls`)
```typescript
{
  hallId: string;
  hallName: string;
  capacity: number;
  facilities: string[];
  active: boolean;
  createdAt: Timestamp;
  updatedAt: Timestamp;
}
```

## 🔌 API Endpoints

### Authentication Routes (`/api/auth`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/register` | Register new user | No |
| GET | `/me` | Get current user profile | Yes |
| PUT | `/profile` | Update user profile | Yes |
| POST | `/logout` | Logout user | Yes |

### Booking Routes (`/api/bookings`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/` | Create new booking | Yes |
| GET | `/my` | Get user's bookings | Yes |
| GET | `/:id` | Get specific booking | Yes |
| PUT | `/:id` | Update booking | Yes |
| DELETE | `/:id` | Cancel booking | Yes |
| GET | `/conflicts/check` | Check booking conflicts | Yes |

### Admin Routes (`/api/admin`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/bookings/pending` | Get pending bookings | Admin |
| GET | `/bookings/approved` | Get approved bookings | Admin |
| GET | `/bookings/rejected` | Get rejected bookings | Admin |
| GET | `/bookings/history` | Get all bookings with filters | Admin |
| PATCH | `/bookings/:id/approve` | Approve booking | Admin |
| PATCH | `/bookings/:id/reject` | Reject booking | Admin |
| GET | `/halls` | Get all halls | Admin |
| POST | `/halls` | Create new hall | Admin |
| PUT | `/halls/:id` | Update hall | Admin |
| DELETE | `/halls/:id` | Delete hall | Admin |
| GET | `/stats` | Get booking statistics | Admin |
| GET | `/users` | Get all users | Admin |
| PATCH | `/users/:id/role` | Update user role | Admin |

## 🚦 Running the Server

### Development Mode
```bash
npm run dev
```

### Production Build
```bash
npm run build
npm start
```

### Testing
```bash
npm test
```

## 🔒 Authentication

The API uses Firebase ID tokens for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <firebase-id-token>
```

### Role-Based Access Control

- **User Role**: Can create and manage their own bookings
- **Admin Role**: Can manage all bookings, halls, and users

## 📝 API Response Format

All API responses follow this consistent format:

```typescript
{
  success: boolean;
  message: string;
  data?: any;
  error?: string;
}
```

### Success Response Example
```json
{
  "success": true,
  "message": "Booking created successfully",
  "data": {
    "booking": {
      "bookingId": "abc123",
      "hallName": "K. S. Krishnan Auditorium",
      "status": "pending"
    }
  }
}
```

### Error Response Example
```json
{
  "success": false,
  "message": "Booking not found",
  "error": "BOOKING_NOT_FOUND"
}
```

## 🛡️ Security Features

- **Rate Limiting**: 100 requests per 15 minutes per IP
- **CORS**: Configured for specific origins
- **Helmet**: Security headers
- **Input Validation**: Request validation and sanitization
- **Firebase Auth**: Secure token-based authentication

## 🔧 Configuration

### Environment Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `PORT` | Server port | 5000 | No |
| `NODE_ENV` | Environment | development | No |
| `FIREBASE_PROJECT_ID` | Firebase project ID | - | **Yes** |
| `FIREBASE_CLIENT_EMAIL` | Firebase service account email | - | **Yes** |
| `FIREBASE_PRIVATE_KEY` | Firebase service account private key | - | **Yes** |
| `FRONTEND_URL` | Frontend URL for CORS | http://localhost:5173 | No |
| `RATE_LIMIT_WINDOW_MS` | Rate limit window | 900000 (15 min) | No |
| `RATE_LIMIT_MAX_REQUESTS` | Max requests per window | 100 | No |

## 📊 Monitoring & Logging

- **Morgan**: HTTP request logging
- **Console Logging**: Error and info logging
- **Health Check**: `/health` endpoint for monitoring

## 🚀 Deployment

1. **Build the project:**
   ```bash
   npm run build
   ```

2. **Set production environment variables**

3. **Start the server:**
   ```bash
   npm start
   ```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions, please contact the KARE Development Team.

---

**KARE College Event Hall Management System Backend API**  
Built with ❤️ using Node.js, Express, TypeScript, and Firebase