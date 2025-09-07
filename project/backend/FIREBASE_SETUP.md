# Firebase Setup Instructions

## Environment Variables Required

The backend now uses environment variables instead of a service account key file. You need to create a `.env` file in the `project/backend/` directory with the following variables:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CLIENT_EMAIL=your-service-account-email@your-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY_HERE\n-----END PRIVATE KEY-----\n"

# Server Configuration
PORT=5000
NODE_ENV=development

# Rate Limiting
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100

# CORS Configuration
FRONTEND_URL=http://localhost:5173
```

## How to Get Firebase Credentials

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Go to Project Settings (gear icon)
4. Go to "Service accounts" tab
5. Click "Generate new private key"
6. Download the JSON file
7. Extract the following values from the JSON:
   - `project_id` → `FIREBASE_PROJECT_ID`
   - `client_email` → `FIREBASE_CLIENT_EMAIL`
   - `private_key` → `FIREBASE_PRIVATE_KEY`

## Important Notes

- The `FIREBASE_PRIVATE_KEY` should include the full private key with `-----BEGIN PRIVATE KEY-----` and `-----END PRIVATE KEY-----`
- Make sure to keep the `.env` file secure and never commit it to version control
- The `\n` characters in the private key will be automatically converted to actual newlines

## Admin User Setup

**Default Admin Credentials:**
- Email: `karehallbooking@gmail.com`
- Password: `Karehallbooking@198`

### Option 1: Using Frontend Setup Page
1. Start the frontend: `npm run dev`
2. Visit `http://localhost:5173/setup-admin`
3. Click "Create Admin User" button

### Option 2: Using Firebase Console
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project → Authentication → Users
3. Click "Add user"
4. Enter email: `karehallbooking@gmail.com`
5. Enter password: `Karehallbooking@198`
6. Create user document in Firestore with admin role

## Testing

After setting up the `.env` file, run:

```bash
npm run dev
```

The server should start without the Firebase initialization error.
